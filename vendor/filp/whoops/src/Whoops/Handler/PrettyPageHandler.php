<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Whoops\Handler;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use UnexpectedValueException;
use Whoops\Exception\Formatter;
use Whoops\Util\Misc;
use Whoops\Util\TemplateHelper;

class PrettyPageHandler extends Handler
{
    /**
     * Search paths to be scanned for resources, in the reverse
     * order they're declared.
     *
     * @var array
     */
    private $searchPaths = [];

    /**
     * Fast lookup cache for known resource locations.
     *
     * @var array
     */
    private $resourceCache = [];

    /**
     * The name of the custom css file.
     *
     * @var string
     */
    private $customCss = null;

    /**
     * @var array[]
     */
    private $extraTables = [];

    /**
     * @var bool
     */
    private $handleUnconditionally = false;

    /**
     * @var string
     */
    private $pageTitle = "Whoops! There was an error.";

    /**
     * @var array[]
     */
    private $applicationPaths;

    /**
     * @var array[]
     */
    private $blacklist = [
        '_GET' => [],
        '_POST' => [],
        '_FILES' => [],
        '_COOKIE' => [],
        '_SESSION' => [],
        '_SERVER' => [],
        '_ENV' => [],
    ];

    /**
     * A string identifier for a known IDE/text editor, or a closure
     * that resolves a string that can be used to open a given file
     * in an editor. If the string contains the special substrings
     * %file or %line, they will be replaced with the correct data.
     *
     * @example
     *  "txmt://open?url=%file&line=%line"
     * @var mixed $editor
     */
    protected $editor;

    /**
     * A list of known editor strings
     * @var array
     */
    protected $editors = [
        "sublime"  => "subl://open?url=file://%file&line=%line",
        "textmate" => "txmt://open?url=file://%file&line=%line",
        "emacs"    => "emacs://open?url=file://%file&line=%line",
        "macvim"   => "mvim://open/?url=file://%file&line=%line",
        "phpstorm" => "phpstorm://open?file=%file&line=%line",
        "idea"     => "idea://open?file=%file&line=%line",
        "vscode"   => "vscode://file/%file:%line",
    ];

    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (ini_get('xdebug.file_link_format') || extension_loaded('xdebug')) {
            // Register editor using xdebug's file_link_format option.
            $this->editors['xdebug'] = function ($file, $line) {
                return str_replace(['%f', '%l'], [$file, $line], ini_get('xdebug.file_link_format'));
            };
        }

        // Add the default, local resource search path:
        $this->searchPaths[] = __DIR__ . "/../Resources";

        // blacklist php provided auth based values
        $this->blacklist('_SERVER', 'PHP_AUTH_PW');

        $this->templateHelper = new TemplateHelper();

        if (class_exists('Symfony\Component\VarDumper\Cloner\VarCloner')) {
            $cloner = new VarCloner();
            // Only dump object internals if a custom caster exists.
            $cloner->addCasters(['*' => function ($obj, $a, $stub, $isNested, $filter = 0) {
                $class = $stub->class;
                $classes = [$class => $class] + class_parents($class) + class_implements($class);

                foreach ($classes as $class) {
                    if (isset(AbstractCloner::$defaultCasters[$class])) {
                        return $a;
                    }
                }

                // Remove all internals
                return [];
            }]);
            $this->templateHelper->setCloner($cloner);
        }
    }

    /**
     * @return int|null
     */
    public function handle()
    {
        if (!$this->handleUnconditionally()) {
            // Check conditions for outputting HTML:
            // @todo: Make this more robust
            if (PHP_SAPI === 'cli') {
                // Help users who have been relying on an internal test value
                // fix their code to the proper method
                if (isset($_ENV['whoops-test'])) {
                    throw new \Exception(
                        'Use handleUnconditionally instead of whoops-test'
                        .' environment variable'
                    );
                }

                return Handler::DONE;
            }
        }

        $templateFile = $this->getResource("views/layout.html.php");
        $cssFile      = $this->getResource("css/whoops.base.css");
        $zeptoFile    = $this->getResource("js/zepto.min.js");
        $prettifyFile = $this->getResource("js/prettify.min.js");
        $clipboard    = $this->getResource("js/clipboard.min.js");
        $jsFile       = $this->getResource("js/whoops.base.js");

        if ($this->customCss) {
            $customCssFile = $this->getResource($this->customCss);
        }

        $inspector = $this->getInspector();
        $frames = $this->getExceptionFrames();
        $code = $this->getExceptionCode();

        // List of variables that will be passed to the layout template.
        $vars = [
            "page_title" => $this->getPageTitle(),

            // @todo: Asset compiler
            "stylesheet" => file_get_contents($cssFile),
            "zepto"      => file_get_contents($zeptoFile),
            "prettify"   => file_get_contents($prettifyFile),
            "clipboard"  => file_get_contents($clipboard),
            "javascript" => file_get_contents($jsFile),

            // Template paths:
            "header"                     => $this->getResource("views/header.html.php"),
            "header_outer"               => $this->getResource("views/header_outer.html.php"),
            "frame_list"                 => $this->getResource("views/frame_list.html.php"),
            "frames_description"         => $this->getResource("views/frames_description.html.php"),
            "frames_container"           => $this->getResource("views/frames_container.html.php"),
            "panel_details"              => $this->getResource("views/panel_details.html.php"),
            "panel_details_outer"        => $this->getResource("views/panel_details_outer.html.php"),
            "panel_left"                 => $this->getResource("views/panel_left.html.php"),
            "panel_left_outer"           => $this->getResource("views/panel_left_outer.html.php"),
            "frame_code"                 => $this->getResource("views/frame_code.html.php"),
            "env_details"                => $this->getResource("views/env_details.html.php"),

            "title"          => $this->getPageTitle(),
            "name"           => explode("\\", $inspector->getExceptionName()),
            "message"        => $inspector->getExceptionMessage(),
            "docref_url"     => $inspector->getExceptionDocrefUrl(),
            "code"           => $code,
            "plain_exception" => Formatter::formatExceptionPlain($inspector),
            "frames"         => $frames,
            "has_frames"     => !!count($frames),
            "handler"        => $this,
            "handlers"       => $this->getRun()->getHandlers(),

            "active_frames_tab" => count($frames) && $frames->offsetGet(0)->isApplication() ?  'application' : 'all',
            "has_frames_tabs"   => $this->getApplicationPaths(),

            "tables"      => [
                "GET Data"              => $this->masked($_GET, '_GET'),
                "POST Data"             => $this->masked($_POST, '_POST'),
                "Files"                 => isset($_FILES) ? $this->masked($_FILES, '_FILES') : [],
                "Cookies"               => $this->masked($_COOKIE, '_COOKIE'),
                "Session"               => isset($_SESSION) ? $this->masked($_SESSION, '_SESSION') :  [],
                "Server/Request Data"   => $this->masked($_SERVER, '_SERVER'),
                "Environment Variables" => $this->masked($_ENV, '_ENV'),
            ],
        ];

        if (isset($customCssFile)) {
            $vars["stylesheet"] .= file_get_contents($customCssFile);
        }

        // Add extra entries list of data tables:
        // @todo: Consolidate addDataTable and addDataTableCallback
        $extraTables = array_map(function ($table) use ($inspector) {
            return $table instanceof \Closure ? $table($inspector) : $table;
        }, $this->getDataTables());
        $vars["tables"] = array_merge($extraTables, $vars["tables"]);

        $plainTextHandler = new PlainTextHandler();
        $plainTextHandler->setException($this->getException());
        $plainTextHandler->setInspector($this->getInspector());
        $vars["preface"] = "<!--\n\n\n" .  $this->templateHelper->escape($plainTextHandler->generateResponse()) . "\n\n\n\n\n\n\n\n\n\n\n-->";

        $this->templateHelper->setVariables($vars);
        $this->templateHelper->render($templateFile);

        return Handler::QUIT;
    }

    /**
     * Get the stack trace frames of the exception that is currently being handled.
     *
     * @return \Whoops\Exception\FrameCollection;
     */
    protected function getExceptionFrames()
    {
        $frames = $this->getInspector()->getFrames();

        if ($this->getApplicationPaths()) {
            foreach ($frames as $frame) {
                foreach ($this->getApplicationPaths() as $path) {
                    if (strpos($frame->getFile(), $path) === 0) {
                        $frame->setApplication(true);
                        break;
                    }
                }
            }
        }

        return $frames;
    }

    /**
     * Get the code of the exception that is currently being handled.
     *
     * @return string
     */
    protected function getExceptionCode()
    {
        $exception = $this->getException();

        $code = $exception->getCode();
        if ($exception instanceof \ErrorException) {
            // ErrorExceptions wrap the php-error types within the 'severity' property
            $code = Misc::translateErrorCode($exception->getSeverity());
        }

        return (string) $code;
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return 'text/html';
    }

    /**
     * Adds an entry to the list of tables displayed in the template.
     * The expected data is a simple associative array. Any nested arrays
     * will be flattened with print_r
     * @param string $label
     * @param array  $data
     */
    public function addDataTable($label, array $data)
    {
        $this->extraTables[$label] = $data;
    }

    /**
     * Lazily adds an entry to the list of tables displayed in the table.
     * The supplied callback argument will be called when the error is rendered,
     * it should produce a simple associative array. Any nested arrays will
     * be flattened with print_r.
     *
     * @throws InvalidArgumentException If $callback is not callable
     * @param  string                   $label
     * @param  callable                 $callback Callable returning an associative array
     */
    public function addDataTableCallback($label, /* callable */ $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expecting callback argument to be callable');
        }

        $this->extraTables[$label] = function (\Whoops\Exception\Inspector $inspector = null) use ($callback) {
            try {
                $result = call_user_func($callback, $inspector);

                // Only return the result if it can be iterated over by foreach().
                return is_array($result) || $result instanceof \Traversable ? $result : [];
            } catch (\Exception $e) {
                // Don't allow failure to break the rendering of the original exception.
                return [];
            }
        };
    }

    /**
     * Returns all the extra data tables registered with this handler.
     * Optionally accepts a 'label' parameter, to only return the data
     * table under that label.
     * @param  string|null      $label
     * @return array[]|callable
     */
    public function getDataTables($label = null)
    {
        if ($label !== null) {
            return isset($this->extraTables[$label]) ?
                   $this->extraTables[$label] : [];
        }

        return $this->extraTables;
    }

    /**
     * Allows to disable all attempts to dynamically decide whether to
     * handle or return prematurely.
     * Set this to ensure that the handler will perform no matter what.
     * @param  bool|null $value
     * @return bool|null
     */
    public function handleUnconditionally($value = null)
    {
        if (func_num_args() == 0) {
            return $this->handleUnconditionally;
        }

        $this->handleUnconditionally = (bool) $value;
    }

    /**
     * Adds an editor resolver, identified by a string
     * name, and that may be a string path, or a callable
     * resolver. If the callable returns a string, it will
     * be set as the file reference's href attribute.
     *
     * @example
     *  $run->addEditor('macvim', "mvim://open?url=file://%file&line=%line")
     * @example
     *   $run->addEditor('remove-it', function($file, $line) {
     *       unlink($file);
     *       return "http://stackoverflow.com";
     *   });
     * @param string $identifier
     * @param string $resolver
     */
    public function addEditor($identifier, $resolver)
    {
        $this->editors[$identifier] = $resolver;
    }

    /**
     * Set the editor to use to open referenced files, by a string
     * identifier, or a callable that will be executed for every
     * file reference, with a $file and $line argument, and should
     * return a string.
     *
     * @example
     *   $run->setEditor(function($file, $line) { return "file:///{$file}"; });
     * @example
     *   $run->setEditor('sublime');
     *
     * @throws InvalidArgumentException If invalid argument identifier provided
     * @param  string|callable          $editor
     */
    public function setEditor($editor)
    {
        if (!is_callable($editor) && !isset($this->editors[$editor])) {
            throw new InvalidArgumentException(
                "Unknown editor identifier: $editor. Known editors:" .
                implode(",", array_keys($this->editors))
            );
        }

        $this->editor = $editor;
    }

    /**
     * Given a string file path, and an integer file line,
     * executes the editor resolver and returns, if available,
     * a string that may be used as the href property for that
     * file reference.
     *
     * @throws InvalidArgumentException If editor resolver does not return a string
     * @param  string                   $filePath
     * @param  int                      $line
     * @return string|bool
     */
    public function getEditorHref($filePath, $line)
    {
        $editor = $this->getEditor($filePath, $line);

        if (empty($editor)) {
            return false;
        }

        // Check that the editor is a string, and replace the
        // %line and %file placeholders:
        if (!isset($editor['url']) || !is_string($editor['url'])) {
            throw new UnexpectedValueException(
                __METHOD__ . " should always resolve to a string or a valid editor array; got something else instead."
            );
        }

        $editor['url'] = str_replace("%line", rawurlencode($line), $editor['url']);
        $editor['url'] = str_replace("%file", rawurlencode($filePath), $editor['url']);

        return $editor['url'];
    }

    /**
     * Given a boolean if the editor link should
     * act as an Ajax request. The editor must be a
     * valid callable function/closure
     *
     * @throws UnexpectedValueException  If editor resolver does not return a boolean
     * @param  string                   $filePath
     * @param  int                      $line
     * @return bool
     */
    public function getEditorAjax($filePath, $line)
    {
        $editor = $this->getEditor($filePath, $line);

        // Check that the ajax is a bool
        if (!isset($editor['ajax']) || !is_bool($editor['ajax'])) {
            throw new UnexpectedValueException(
                __METHOD__ . " should always resolve to a bool; got something else instead."
            );
        }
        return $editor['ajax'];
    }

    /**
     * Given a boolean if the editor link should
     * act as an Ajax request. The editor must be a
     * valid callable function/closure
     *
     * @param  string $filePath
     * @param  int    $line
     * @return array
     */
    protected function getEditor($filePath, $line)
    {
        if (!$this->editor || (!is_string($this->editor) && !is_callable($this->editor))) {
            return [];
        }

        if (is_string($this->editor) && isset($this->editors[$this->editor]) && !is_callable($this->editors[$this->editor])) {
            return [
                'ajax' => false,
                'url' => $this->editors[$this->editor],
            ];
        }

        if (is_callable($this->editor) || (isset($this->editors[$this->editor]) && is_callable($this->editors[$this->editor]))) {
            if (is_callable($this->editor)) {
                $callback = call_user_func($this->editor, $filePath, $line);
            } else {
                $callback = call_user_func($this->editors[$this->editor], $filePath, $line);
            }

            if (is_string($callback)) {
                return [
                    'ajax' => false,
                    'url' => $callback,
                ];
            }

            return [
                'ajax' => isset($callback['ajax']) ? $callback['ajax'] : false,
                'url' => isset($callback['url']) ? $callback['url'] : $callback,
            ];
        }

        return [];
    }

    /**
     * @param  string $title
     * @return void
     */
    public function setPageTitle($title)
    {
        $this->pageTitle = (string) $title;
    }

    /**
     * @return string
     */
    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    /**
     * Adds a path to the list of paths to be searched for
     * resources.
     *
     * @throws InvalidArgumentException If $path is not a valid directory
     *
     * @param  string $path
     * @return void
     */
    public function addResourcePath($path)
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(
                "'$path' is not a valid directory"
            );
        }

        array_unshift($this->searchPaths, $path);
    }

    /**
     * Adds a custom css file to be loaded.
     *
     * @param  string $name
     * @return void
     */
    public function addCustomCss($name)
    {
        $this->customCss = $name;
    }

    /**
     * @return array
     */
    public function getResourcePaths()
    {
        return $this->searchPaths;
    }

    /**
     * Finds a resource, by its relative path, in all available search paths.
     * The search is performed starting at the last search path, and all the
     * way back to the first, enabling a cascading-type system of overrides
     * for all resources.
     *
     * @throws RuntimeException If resource cannot be found in any of the available paths
     *
     * @param  string $resource
     * @return string
     */
    protected function getResource($resource)
    {
        // If the resource was found before, we can speed things up
        // by caching its absolute, resolved path:
        if (isset($this->resourceCache[$resource])) {
            return $this->resourceCache[$resource];
        }

        // Search through available search paths, until we find the
        // resource we're after:
        foreach ($this->searchPaths as $path) {
            $fullPath = $path . "/$resource";

            if (is_file($fullPath)) {
                // Cache the result:
                $this->resourceCache[$resource] = $fullPath;
                return $fullPath;
            }
        }

        // If we got this far, nothing was found.
        throw new RuntimeException(
            "Could not find resource '$resource' in any resource paths."
            . "(searched: " . join(", ", $this->searchPaths). ")"
        );
    }

    /**
     * @deprecated
     *
     * @return string
     */
    public function getResourcesPath()
    {
        $allPaths = $this->getResourcePaths();

        // Compat: return only the first path added
        return end($allPaths) ?: null;
    }

    /**
     * @deprecated
     *
     * @param  string $resourcesPath
     * @return void
     */
    public function setResourcesPath($resourcesPath)
    {
        $this->addResourcePath($resourcesPath);
    }

    /**
     * Return the application paths.
     *
     * @return array
     */
    public function getApplicationPaths()
    {
        return $this->applicationPaths;
    }

    /**
     * Set the application paths.
     *
     * @param array $applicationPaths
     */
    public function setApplicationPaths($applicationPaths)
    {
        $this->applicationPaths = $applicationPaths;
    }

    /**
     * Set the application root path.
     *
     * @param string $applicationRootPath
     */
    public function setApplicationRootPath($applicationRootPath)
    {
        $this->templateHelper->setApplicationRootPath($applicationRootPath);
    }

    /**
     * blacklist a sensitive value within one of the superglobal arrays.
     *
     * @param $superGlobalName string the name of the superglobal array, e.g. '_GET'
     * @param $key string the key within the superglobal
     */
    public function blacklist($superGlobalName, $key)
    {
        $this->blacklist[$superGlobalName][] = $key;
    }

    /**
     * Checks all values within the given superGlobal array.
     * Blacklisted values will be replaced by a equal length string cointaining only '*' characters.
     *
     * We intentionally dont rely on $GLOBALS as it depends on 'auto_globals_jit' php.ini setting.
     *
     * @param $superGlobal array One of the superglobal arrays
     * @param $superGlobalName string the name of the superglobal array, e.g. '_GET'
     * @return array $values without sensitive data
     */
    private function masked(array $superGlobal, $superGlobalName)
    {
        $blacklisted = $this->blacklist[$superGlobalName];

        $values = $superGlobal;
        foreach ($blacklisted as $key) {
            if (isset($superGlobal[$key])) {
                $values[$key] = str_repeat('*', strlen($superGlobal[$key]));
            }
        }
        return $values;
    }
}
