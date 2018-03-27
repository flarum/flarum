<?php

require_once( dirname(__FILE__).'/Cache.php');

/**
 * Class for parsing and compiling less files into css
 *
 * @package Less
 * @subpackage parser
 *
 */
class Less_Parser{


	/**
	 * Default parser options
	 */
	public static $default_options = array(
		'compress'				=> false,			// option - whether to compress
		'strictUnits'			=> false,			// whether units need to evaluate correctly
		'strictMath'			=> false,			// whether math has to be within parenthesis
		'relativeUrls'			=> true,			// option - whether to adjust URL's to be relative
		'urlArgs'				=> '',				// whether to add args into url tokens
		'numPrecision'			=> 8,

		'import_dirs'			=> array(),
		'import_callback'		=> null,
		'cache_dir'				=> null,
		'cache_method'			=> 'php', 			// false, 'serialize', 'php', 'var_export', 'callback';
		'cache_callback_get'	=> null,
		'cache_callback_set'	=> null,

		'sourceMap'				=> false,			// whether to output a source map
		'sourceMapBasepath'		=> null,
		'sourceMapWriteTo'		=> null,
		'sourceMapURL'			=> null,

		'indentation' 			=> '  ',

		'plugins'				=> array(),

	);

	public static $options = array();


	private $input;					// Less input string
	private $input_len;				// input string length
	private $pos;					// current index in `input`
	private $saveStack = array();	// holds state for backtracking
	private $furthest;
	private $mb_internal_encoding = ''; // for remember exists value of mbstring.internal_encoding

	/**
	 * @var Less_Environment
	 */
	private $env;

	protected $rules = array();

	private static $imports = array();

	public static $has_extends = false;

	public static $next_id = 0;

	/**
	 * Filename to contents of all parsed the files
	 *
	 * @var array
	 */
	public static $contentsMap = array();


	/**
	 * @param Less_Environment|array|null $env
	 */
	public function __construct( $env = null ){

		// Top parser on an import tree must be sure there is one "env"
		// which will then be passed around by reference.
		if( $env instanceof Less_Environment ){
			$this->env = $env;
		}else{
			$this->SetOptions(Less_Parser::$default_options);
			$this->Reset( $env );
		}

		// mbstring.func_overload > 1 bugfix
		// The encoding value must be set for each source file,
		// therefore, to conserve resources and improve the speed of this design is taken here
		if (ini_get('mbstring.func_overload')) {
			$this->mb_internal_encoding = ini_get('mbstring.internal_encoding');
			@ini_set('mbstring.internal_encoding', 'ascii');
		}

	}


	/**
	 * Reset the parser state completely
	 *
	 */
	public function Reset( $options = null ){
		$this->rules = array();
		self::$imports = array();
		self::$has_extends = false;
		self::$imports = array();
		self::$contentsMap = array();

		$this->env = new Less_Environment($options);

		//set new options
		if( is_array($options) ){
			$this->SetOptions(Less_Parser::$default_options);
			$this->SetOptions($options);
		}

		$this->env->Init();
	}

	/**
	 * Set one or more compiler options
	 *  options: import_dirs, cache_dir, cache_method
	 *
	 */
	public function SetOptions( $options ){
		foreach($options as $option => $value){
			$this->SetOption($option,$value);
		}
	}

	/**
	 * Set one compiler option
	 *
	 */
	public function SetOption($option,$value){

		switch($option){

			case 'import_dirs':
				$this->SetImportDirs($value);
			return;

			case 'cache_dir':
				if( is_string($value) ){
					Less_Cache::SetCacheDir($value);
					Less_Cache::CheckCacheDir();
				}
			return;
		}

		Less_Parser::$options[$option] = $value;
	}

	/**
	 * Registers a new custom function
	 *
	 * @param  string   $name     function name
	 * @param  callable $callback callback
	 */
	public function registerFunction($name, $callback) {
		$this->env->functions[$name] = $callback;
	}

	/**
	 * Removed an already registered function
	 *
	 * @param  string $name function name
	 */
	public function unregisterFunction($name) {
		if( isset($this->env->functions[$name]) )
			unset($this->env->functions[$name]);
	}


	/**
	 * Get the current css buffer
	 *
	 * @return string
	 */
	public function getCss(){

		$precision = ini_get('precision');
		@ini_set('precision',16);
		$locale = setlocale(LC_NUMERIC, 0);
		setlocale(LC_NUMERIC, "C");

		try {

	 		$root = new Less_Tree_Ruleset(array(), $this->rules );
			$root->root = true;
			$root->firstRoot = true;


			$this->PreVisitors($root);

			self::$has_extends = false;
			$evaldRoot = $root->compile($this->env);



			$this->PostVisitors($evaldRoot);

			if( Less_Parser::$options['sourceMap'] ){
				$generator = new Less_SourceMap_Generator($evaldRoot, Less_Parser::$contentsMap, Less_Parser::$options );
				// will also save file
				// FIXME: should happen somewhere else?
				$css = $generator->generateCSS();
			}else{
				$css = $evaldRoot->toCSS();
			}

			if( Less_Parser::$options['compress'] ){
				$css = preg_replace('/(^(\s)+)|((\s)+$)/', '', $css);
			}

		} catch (Exception $exc) {
			// Intentional fall-through so we can reset environment
		}

		//reset php settings
		@ini_set('precision',$precision);
		setlocale(LC_NUMERIC, $locale);

		// If you previously defined $this->mb_internal_encoding
		// is required to return the encoding as it was before
		if ($this->mb_internal_encoding != '') {
			@ini_set("mbstring.internal_encoding", $this->mb_internal_encoding);
			$this->mb_internal_encoding = '';
		}

		// Rethrow exception after we handled resetting the environment
		if (!empty($exc)) {
			throw $exc;
		}

		return $css;
	}

	public function findValueOf($varName)
	{
		foreach($this->rules as $rule){
			if(isset($rule->variable) && ($rule->variable == true) && (str_replace("@","",$rule->name) == $varName)){
				return $this->getVariableValue($rule);
			}
		}
		return null;
	}

	/**
	 *
	 * this function gets the private rules variable and returns an array of the found variables
	 * it uses a helper method getVariableValue() that contains the logic ot fetch the value from the rule object
	 *
	 * @return array
	 */
	public function getVariables()
	{
		$variables = array();

		$not_variable_type = array(
			'Comment',   // this include less comments ( // ) and css comments (/* */)
			'Import',    // do not search variables in included files @import
			'Ruleset',   // selectors (.someclass, #someid, …)
			'Operation', //
		);

		// @TODO run compilation if not runned yet
		foreach ($this->rules as $key => $rule) {
			if (in_array($rule->type, $not_variable_type)) {
				continue;
			}

			// Note: it seems rule->type is always Rule when variable = true
			if ($rule->type == 'Rule' && $rule->variable) {
				$variables[$rule->name] = $this->getVariableValue($rule);
			} else {
				if ($rule->type == 'Comment') {
					$variables[] = $this->getVariableValue($rule);
				}
			}
		}
		return $variables;
	}

	public function findVarByName($var_name)
	{
		foreach($this->rules as $rule){
			if(isset($rule->variable) && ($rule->variable == true)){
				if($rule->name == $var_name){
					return $this->getVariableValue($rule);
				}
			}
		}
		return null;
	}

	/**
	 *
	 * This method gets the value of the less variable from the rules object.
	 * Since the objects vary here we add the logic for extracting the css/less value.
	 *
	 * @param $var
	 *
	 * @return bool|string
	 */
	private function getVariableValue($var)
	{
		if (!is_a($var, 'Less_Tree')) {
			throw new Exception('var is not a Less_Tree object');
		}

		switch ($var->type) {
			case 'Color':
				return $this->rgb2html($var->rgb);
			case 'Unit':
				return $var->value. $var->unit->numerator[0];
			case 'Variable':
				return $this->findVarByName($var->name);
			case 'Keyword':
				return $var->value;
			case 'Rule':
				return $this->getVariableValue($var->value);
			case 'Value':
				$value = '';
				foreach ($var->value as $sub_value) {
					$value .= $this->getVariableValue($sub_value).' ';
				}
				return $value;
			case 'Quoted':
				return $var->quote.$var->value.$var->quote;
			case 'Dimension':
				$value = $var->value;
				if ($var->unit && $var->unit->numerator) {
					$value .= $var->unit->numerator[0];
				}
				return $value;
			case 'Expression':
				$value = "";
				foreach($var->value as $item) {
					$value .= $this->getVariableValue($item)." ";
				}
				return $value;
			case 'Operation':
				throw new Exception('getVariables() require Less to be compiled. please use $parser->getCss() before calling getVariables()');
			case 'Comment':
			case 'Import':
			case 'Ruleset':
			default:
				throw new Exception("type missing in switch/case getVariableValue for ".$var->type);
		}
		return false;
	}

	private function rgb2html($r, $g=-1, $b=-1)
	{
		if (is_array($r) && sizeof($r) == 3)
			list($r, $g, $b) = $r;

		$r = intval($r); $g = intval($g);
		$b = intval($b);

		$r = dechex($r<0?0:($r>255?255:$r));
		$g = dechex($g<0?0:($g>255?255:$g));
		$b = dechex($b<0?0:($b>255?255:$b));

		$color = (strlen($r) < 2?'0':'').$r;
		$color .= (strlen($g) < 2?'0':'').$g;
		$color .= (strlen($b) < 2?'0':'').$b;
		return '#'.$color;
	}

	/**
	 * Run pre-compile visitors
	 *
	 */
	private function PreVisitors($root){

		if( Less_Parser::$options['plugins'] ){
			foreach(Less_Parser::$options['plugins'] as $plugin){
				if( !empty($plugin->isPreEvalVisitor) ){
					$plugin->run($root);
				}
			}
		}
	}


	/**
	 * Run post-compile visitors
	 *
	 */
	private function PostVisitors($evaldRoot){

		$visitors = array();
		$visitors[] = new Less_Visitor_joinSelector();
		if( self::$has_extends ){
			$visitors[] = new Less_Visitor_processExtends();
		}
		$visitors[] = new Less_Visitor_toCSS();


		if( Less_Parser::$options['plugins'] ){
			foreach(Less_Parser::$options['plugins'] as $plugin){
				if( property_exists($plugin,'isPreEvalVisitor') && $plugin->isPreEvalVisitor ){
					continue;
				}

				if( property_exists($plugin,'isPreVisitor') && $plugin->isPreVisitor ){
					array_unshift( $visitors, $plugin);
				}else{
					$visitors[] = $plugin;
				}
			}
		}


		for($i = 0; $i < count($visitors); $i++ ){
			$visitors[$i]->run($evaldRoot);
		}

	}


	/**
	 * Parse a Less string into css
	 *
	 * @param string $str The string to convert
	 * @param string $uri_root The url of the file
	 * @return Less_Tree_Ruleset|Less_Parser
	 */
	public function parse( $str, $file_uri = null ){

		if( !$file_uri ){
			$uri_root = '';
			$filename = 'anonymous-file-'.Less_Parser::$next_id++.'.less';
		}else{
			$file_uri = self::WinPath($file_uri);
			$filename = $file_uri;
			$uri_root = dirname($file_uri);
		}

		$previousFileInfo = $this->env->currentFileInfo;
		$uri_root = self::WinPath($uri_root);
		$this->SetFileInfo($filename, $uri_root);

		$this->input = $str;
		$this->_parse();

		if( $previousFileInfo ){
			$this->env->currentFileInfo = $previousFileInfo;
		}

		return $this;
	}


	/**
	 * Parse a Less string from a given file
	 *
	 * @throws Less_Exception_Parser
	 * @param string $filename The file to parse
	 * @param string $uri_root The url of the file
	 * @param bool $returnRoot Indicates whether the return value should be a css string a root node
	 * @return Less_Tree_Ruleset|Less_Parser
	 */
	public function parseFile( $filename, $uri_root = '', $returnRoot = false){

		if( !file_exists($filename) ){
			$this->Error(sprintf('File `%s` not found.', $filename));
		}


		// fix uri_root?
		// Instead of The mixture of file path for the first argument and directory path for the second argument has bee
		if( !$returnRoot && !empty($uri_root) && basename($uri_root) == basename($filename) ){
			$uri_root = dirname($uri_root);
		}


		$previousFileInfo = $this->env->currentFileInfo;


		if( $filename ){
			$filename = self::AbsPath($filename, true);
		}
		$uri_root = self::WinPath($uri_root);

		$this->SetFileInfo($filename, $uri_root);

		self::AddParsedFile($filename);

		if( $returnRoot ){
			$rules = $this->GetRules( $filename );
			$return = new Less_Tree_Ruleset(array(), $rules );
		}else{
			$this->_parse( $filename );
			$return = $this;
		}

		if( $previousFileInfo ){
			$this->env->currentFileInfo = $previousFileInfo;
		}

		return $return;
	}


	/**
	 * Allows a user to set variables values
	 * @param array $vars
	 * @return Less_Parser
	 */
	public function ModifyVars( $vars ){

		$this->input = Less_Parser::serializeVars( $vars );
		$this->_parse();

		return $this;
	}


	/**
	 * @param string $filename
	 */
	public function SetFileInfo( $filename, $uri_root = ''){

		$filename = Less_Environment::normalizePath($filename);
		$dirname = preg_replace('/[^\/\\\\]*$/','',$filename);

		if( !empty($uri_root) ){
			$uri_root = rtrim($uri_root,'/').'/';
		}

		$currentFileInfo = array();

		//entry info
		if( isset($this->env->currentFileInfo) ){
			$currentFileInfo['entryPath'] = $this->env->currentFileInfo['entryPath'];
			$currentFileInfo['entryUri'] = $this->env->currentFileInfo['entryUri'];
			$currentFileInfo['rootpath'] = $this->env->currentFileInfo['rootpath'];

		}else{
			$currentFileInfo['entryPath'] = $dirname;
			$currentFileInfo['entryUri'] = $uri_root;
			$currentFileInfo['rootpath'] = $dirname;
		}

		$currentFileInfo['currentDirectory'] = $dirname;
		$currentFileInfo['currentUri'] = $uri_root.basename($filename);
		$currentFileInfo['filename'] = $filename;
		$currentFileInfo['uri_root'] = $uri_root;


		//inherit reference
		if( isset($this->env->currentFileInfo['reference']) && $this->env->currentFileInfo['reference'] ){
			$currentFileInfo['reference'] = true;
		}

		$this->env->currentFileInfo = $currentFileInfo;
	}


	/**
	 * @deprecated 1.5.1.2
	 *
	 */
	public function SetCacheDir( $dir ){

		if( !file_exists($dir) ){
			if( mkdir($dir) ){
				return true;
			}
			throw new Less_Exception_Parser('Less.php cache directory couldn\'t be created: '.$dir);

		}elseif( !is_dir($dir) ){
			throw new Less_Exception_Parser('Less.php cache directory doesn\'t exist: '.$dir);

		}elseif( !is_writable($dir) ){
			throw new Less_Exception_Parser('Less.php cache directory isn\'t writable: '.$dir);

		}else{
			$dir = self::WinPath($dir);
			Less_Cache::$cache_dir = rtrim($dir,'/').'/';
			return true;
		}
	}


	/**
	 * Set a list of directories or callbacks the parser should use for determining import paths
	 *
	 * @param array $dirs
	 */
	public function SetImportDirs( $dirs ){
		Less_Parser::$options['import_dirs'] = array();

		foreach($dirs as $path => $uri_root){

			$path = self::WinPath($path);
			if( !empty($path) ){
				$path = rtrim($path,'/').'/';
			}

			if ( !is_callable($uri_root) ){
				$uri_root = self::WinPath($uri_root);
				if( !empty($uri_root) ){
					$uri_root = rtrim($uri_root,'/').'/';
				}
			}

			Less_Parser::$options['import_dirs'][$path] = $uri_root;
		}
	}

	/**
	 * @param string $file_path
	 */
	private function _parse( $file_path = null ){
		$this->rules = array_merge($this->rules, $this->GetRules( $file_path ));
	}


	/**
	 * Return the results of parsePrimary for $file_path
	 * Use cache and save cached results if possible
	 *
	 * @param string|null $file_path
	 */
	private function GetRules( $file_path ){

		$this->SetInput($file_path);

		$cache_file = $this->CacheFile( $file_path );
		if( $cache_file ){
			if( Less_Parser::$options['cache_method'] == 'callback' ){
				if( is_callable(Less_Parser::$options['cache_callback_get']) ){
					$cache = call_user_func_array(
						Less_Parser::$options['cache_callback_get'],
						array($this, $file_path, $cache_file)
					);

					if( $cache ){
						$this->UnsetInput();
						return $cache;
					}
				}

			}elseif( file_exists($cache_file) ){
				switch(Less_Parser::$options['cache_method']){

					// Using serialize
					// Faster but uses more memory
					case 'serialize':
						$cache = unserialize(file_get_contents($cache_file));
						if( $cache ){
							touch($cache_file);
							$this->UnsetInput();
							return $cache;
						}
						break;


						// Using generated php code
					case 'var_export':
					case 'php':
						$this->UnsetInput();
						return include($cache_file);
				}
			}
		}

		$rules = $this->parsePrimary();

		if( $this->pos < $this->input_len ){
			throw new Less_Exception_Chunk($this->input, null, $this->furthest, $this->env->currentFileInfo);
		}

		$this->UnsetInput();


		//save the cache
		if( $cache_file ){
			if( Less_Parser::$options['cache_method'] == 'callback' ){
				if( is_callable(Less_Parser::$options['cache_callback_set']) ){
					call_user_func_array(
						Less_Parser::$options['cache_callback_set'],
						array($this, $file_path, $cache_file, $rules)
					);
				}

			}else{
				//msg('write cache file');
				switch(Less_Parser::$options['cache_method']){
					case 'serialize':
						file_put_contents( $cache_file, serialize($rules) );
						break;
					case 'php':
						file_put_contents( $cache_file, '<?php return '.self::ArgString($rules).'; ?>' );
						break;
					case 'var_export':
						//Requires __set_state()
						file_put_contents( $cache_file, '<?php return '.var_export($rules,true).'; ?>' );
						break;
				}

				Less_Cache::CleanCache();
			}
		}

		return $rules;
	}


	/**
	 * Set up the input buffer
	 *
	 */
	public function SetInput( $file_path ){

		if( $file_path ){
			$this->input = file_get_contents( $file_path );
		}

		$this->pos = $this->furthest = 0;

		// Remove potential UTF Byte Order Mark
		$this->input = preg_replace('/\\G\xEF\xBB\xBF/', '', $this->input);
		$this->input_len = strlen($this->input);


		if( Less_Parser::$options['sourceMap'] && $this->env->currentFileInfo ){
			$uri = $this->env->currentFileInfo['currentUri'];
			Less_Parser::$contentsMap[$uri] = $this->input;
		}

	}


	/**
	 * Free up some memory
	 *
	 */
	public function UnsetInput(){
		unset($this->input, $this->pos, $this->input_len, $this->furthest);
		$this->saveStack = array();
	}


	public function CacheFile( $file_path ){

		if( $file_path && $this->CacheEnabled() ){

			$env = get_object_vars($this->env);
			unset($env['frames']);

			$parts = array();
			$parts[] = $file_path;
			$parts[] = filesize( $file_path );
			$parts[] = filemtime( $file_path );
			$parts[] = $env;
			$parts[] = Less_Version::cache_version;
			$parts[] = Less_Parser::$options['cache_method'];
			return Less_Cache::$cache_dir . Less_Cache::$prefix . base_convert( sha1(json_encode($parts) ), 16, 36) . '.lesscache';
		}
	}


	static function AddParsedFile($file){
		self::$imports[] = $file;
	}

	static function AllParsedFiles(){
		return self::$imports;
	}

	/**
	 * @param string $file
	 */
	static function FileParsed($file){
		return in_array($file,self::$imports);
	}


	function save() {
		$this->saveStack[] = $this->pos;
	}

	private function restore() {
		$this->pos = array_pop($this->saveStack);
	}

	private function forget(){
		array_pop($this->saveStack);
	}

	/**
	 * Determine if the character at the specified offset from the current position is a white space.
	 *
	 * @param int $offset
	 *
	 * @return bool
	 */
	private function isWhitespace($offset = 0) {
		return strpos(" \t\n\r\v\f", $this->input[$this->pos + $offset]) !== false;
	}

	/**
	 * Parse from a token, regexp or string, and move forward if match
	 *
	 * @param array $toks
	 * @return array
	 */
	private function match($toks){

		// The match is confirmed, add the match length to `this::pos`,
		// and consume any extra white-space characters (' ' || '\n')
		// which come after that. The reason for this is that LeSS's
		// grammar is mostly white-space insensitive.
		//

		foreach($toks as $tok){

			$char = $tok[0];

			if( $char === '/' ){
				$match = $this->MatchReg($tok);

				if( $match ){
					return count($match) === 1 ? $match[0] : $match;
				}

			}elseif( $char === '#' ){
				$match = $this->MatchChar($tok[1]);

			}else{
				// Non-terminal, match using a function call
				$match = $this->$tok();

			}

			if( $match ){
				return $match;
			}
		}
	}

	/**
	 * @param string[] $toks
	 *
	 * @return string
	 */
	private function MatchFuncs($toks){

		if( $this->pos < $this->input_len ){
			foreach($toks as $tok){
				$match = $this->$tok();
				if( $match ){
					return $match;
				}
			}
		}

	}

	// Match a single character in the input,
	private function MatchChar($tok){
		if( ($this->pos < $this->input_len) && ($this->input[$this->pos] === $tok) ){
			$this->skipWhitespace(1);
			return $tok;
		}
	}

	// Match a regexp from the current start point
	private function MatchReg($tok){

		if( preg_match($tok, $this->input, $match, 0, $this->pos) ){
			$this->skipWhitespace(strlen($match[0]));
			return $match;
		}
	}


	/**
	 * Same as match(), but don't change the state of the parser,
	 * just return the match.
	 *
	 * @param string $tok
	 * @return integer
	 */
	public function PeekReg($tok){
		return preg_match($tok, $this->input, $match, 0, $this->pos);
	}

	/**
	 * @param string $tok
	 */
	public function PeekChar($tok){
		//return ($this->input[$this->pos] === $tok );
		return ($this->pos < $this->input_len) && ($this->input[$this->pos] === $tok );
	}


	/**
	 * @param integer $length
	 */
	public function skipWhitespace($length){

		$this->pos += $length;

		for(; $this->pos < $this->input_len; $this->pos++ ){
			$c = $this->input[$this->pos];

			if( ($c !== "\n") && ($c !== "\r") && ($c !== "\t") && ($c !== ' ') ){
				break;
			}
		}
	}


	/**
	 * @param string $tok
	 * @param string|null $msg
	 */
	public function expect($tok, $msg = NULL) {
		$result = $this->match( array($tok) );
		if (!$result) {
			$this->Error( $msg	? "Expected '" . $tok . "' got '" . $this->input[$this->pos] . "'" : $msg );
		} else {
			return $result;
		}
	}

	/**
	 * @param string $tok
	 */
	public function expectChar($tok, $msg = null ){
		$result = $this->MatchChar($tok);
		if( !$result ){
			$msg = $msg ? $msg : "Expected '" . $tok . "' got '" . $this->input[$this->pos] . "'";
			$this->Error( $msg );
		}else{
			return $result;
		}
	}

	//
	// Here in, the parsing rules/functions
	//
	// The basic structure of the syntax tree generated is as follows:
	//
	//   Ruleset ->  Rule -> Value -> Expression -> Entity
	//
	// Here's some LESS code:
	//
	//	.class {
	//	  color: #fff;
	//	  border: 1px solid #000;
	//	  width: @w + 4px;
	//	  > .child {...}
	//	}
	//
	// And here's what the parse tree might look like:
	//
	//	 Ruleset (Selector '.class', [
	//		 Rule ("color",  Value ([Expression [Color #fff]]))
	//		 Rule ("border", Value ([Expression [Dimension 1px][Keyword "solid"][Color #000]]))
	//		 Rule ("width",  Value ([Expression [Operation "+" [Variable "@w"][Dimension 4px]]]))
	//		 Ruleset (Selector [Element '>', '.child'], [...])
	//	 ])
	//
	//  In general, most rules will try to parse a token with the `$()` function, and if the return
	//  value is truly, will return a new node, of the relevant type. Sometimes, we need to check
	//  first, before parsing, that's when we use `peek()`.
	//

	//
	// The `primary` rule is the *entry* and *exit* point of the parser.
	// The rules here can appear at any level of the parse tree.
	//
	// The recursive nature of the grammar is an interplay between the `block`
	// rule, which represents `{ ... }`, the `ruleset` rule, and this `primary` rule,
	// as represented by this simplified grammar:
	//
	//	 primary  →  (ruleset | rule)+
	//	 ruleset  →  selector+ block
	//	 block	→  '{' primary '}'
	//
	// Only at one point is the primary rule not called from the
	// block rule: at the root level.
	//
	private function parsePrimary(){
		$root = array();

		while( true ){

			if( $this->pos >= $this->input_len ){
				break;
			}

			$node = $this->parseExtend(true);
			if( $node ){
				$root = array_merge($root,$node);
				continue;
			}

			//$node = $this->MatchFuncs( array( 'parseMixinDefinition', 'parseRule', 'parseRuleset', 'parseMixinCall', 'parseComment', 'parseDirective'));
			$node = $this->MatchFuncs( array( 'parseMixinDefinition', 'parseNameValue', 'parseRule', 'parseRuleset', 'parseMixinCall', 'parseComment', 'parseRulesetCall', 'parseDirective'));

			if( $node ){
				$root[] = $node;
			}elseif( !$this->MatchReg('/\\G[\s\n;]+/') ){
				break;
			}

			if( $this->PeekChar('}') ){
				break;
			}
		}

		return $root;
	}



	// We create a Comment node for CSS comments `/* */`,
	// but keep the LeSS comments `//` silent, by just skipping
	// over them.
	private function parseComment(){

		if( $this->input[$this->pos] !== '/' ){
			return;
		}

		if( $this->input[$this->pos+1] === '/' ){
			$match = $this->MatchReg('/\\G\/\/.*/');
			return $this->NewObj4('Less_Tree_Comment',array($match[0], true, $this->pos, $this->env->currentFileInfo));
		}

		//$comment = $this->MatchReg('/\\G\/\*(?:[^*]|\*+[^\/*])*\*+\/\n?/');
		$comment = $this->MatchReg('/\\G\/\*(?s).*?\*+\/\n?/');//not the same as less.js to prevent fatal errors
		if( $comment ){
			return $this->NewObj4('Less_Tree_Comment',array($comment[0], false, $this->pos, $this->env->currentFileInfo));
		}
	}

	private function parseComments(){
		$comments = array();

		while( $this->pos < $this->input_len ){
			$comment = $this->parseComment();
			if( !$comment ){
				break;
			}

			$comments[] = $comment;
		}

		return $comments;
	}



	//
	// A string, which supports escaping " and '
	//
	//	 "milky way" 'he\'s the one!'
	//
	private function parseEntitiesQuoted() {
		$j = $this->pos;
		$e = false;
		$index = $this->pos;

		if( $this->input[$this->pos] === '~' ){
			$j++;
			$e = true; // Escaped strings
		}

		$char = $this->input[$j];
		if( $char !== '"' && $char !== "'" ){
			return;
		}

		if ($e) {
			$this->MatchChar('~');
		}


		$matched = $this->MatchQuoted($char, $j+1);
		if( $matched === false ){
			return;
		}

		$quoted = $char.$matched.$char;
		return $this->NewObj5('Less_Tree_Quoted',array($quoted, $matched, $e, $index, $this->env->currentFileInfo) );
	}


	/**
	 * When PCRE JIT is enabled in php, regular expressions don't work for matching quoted strings
	 *
	 *	$regex	= '/\\G\'((?:[^\'\\\\\r\n]|\\\\.|\\\\\r\n|\\\\[\n\r\f])*)\'/';
	 *	$regex	= '/\\G"((?:[^"\\\\\r\n]|\\\\.|\\\\\r\n|\\\\[\n\r\f])*)"/';
	 *
	 */
	private function MatchQuoted($quote_char, $i){

		$matched = '';
		while( $i < $this->input_len ){
			$c = $this->input[$i];

			//escaped character
			if( $c === '\\' ){
				$matched .= $c . $this->input[$i+1];
				$i += 2;
				continue;
			}

			if( $c === $quote_char ){
				$this->pos = $i+1;
				$this->skipWhitespace(0);
				return $matched;
			}

			if( $c === "\r" || $c === "\n" ){
				return false;
			}

			$i++;
			$matched .= $c;
		}

		return false;
	}


	//
	// A catch-all word, such as:
	//
	//	 black border-collapse
	//
	private function parseEntitiesKeyword(){

		//$k = $this->MatchReg('/\\G[_A-Za-z-][_A-Za-z0-9-]*/');
		$k = $this->MatchReg('/\\G%|\\G[_A-Za-z-][_A-Za-z0-9-]*/');
		if( $k ){
			$k = $k[0];
			$color = $this->fromKeyword($k);
			if( $color ){
				return $color;
			}
			return $this->NewObj1('Less_Tree_Keyword',$k);
		}
	}

	// duplicate of Less_Tree_Color::FromKeyword
	private function FromKeyword( $keyword ){
		$keyword = strtolower($keyword);

		if( Less_Colors::hasOwnProperty($keyword) ){
			// detect named color
			return $this->NewObj1('Less_Tree_Color',substr(Less_Colors::color($keyword), 1));
		}

		if( $keyword === 'transparent' ){
			return $this->NewObj3('Less_Tree_Color', array( array(0, 0, 0), 0, true));
		}
	}

	//
	// A function call
	//
	//	 rgb(255, 0, 255)
	//
	// We also try to catch IE's `alpha()`, but let the `alpha` parser
	// deal with the details.
	//
	// The arguments are parsed with the `entities.arguments` parser.
	//
	private function parseEntitiesCall(){
		$index = $this->pos;

		if( !preg_match('/\\G([\w-]+|%|progid:[\w\.]+)\(/', $this->input, $name,0,$this->pos) ){
			return;
		}
		$name = $name[1];
		$nameLC = strtolower($name);

		if ($nameLC === 'url') {
			return null;
		}

		$this->pos += strlen($name);

		if( $nameLC === 'alpha' ){
			$alpha_ret = $this->parseAlpha();
			if( $alpha_ret ){
				return $alpha_ret;
			}
		}

		$this->MatchChar('('); // Parse the '(' and consume whitespace.

		$args = $this->parseEntitiesArguments();

		if( !$this->MatchChar(')') ){
			return;
		}

		if ($name) {
			return $this->NewObj4('Less_Tree_Call',array($name, $args, $index, $this->env->currentFileInfo) );
		}
	}

	/**
	 * Parse a list of arguments
	 *
	 * @return array
	 */
	private function parseEntitiesArguments(){

		$args = array();
		while( true ){
			$arg = $this->MatchFuncs( array('parseEntitiesAssignment','parseExpression') );
			if( !$arg ){
				break;
			}

			$args[] = $arg;
			if( !$this->MatchChar(',') ){
				break;
			}
		}
		return $args;
	}

	private function parseEntitiesLiteral(){
		return $this->MatchFuncs( array('parseEntitiesDimension','parseEntitiesColor','parseEntitiesQuoted','parseUnicodeDescriptor') );
	}

	// Assignments are argument entities for calls.
	// They are present in ie filter properties as shown below.
	//
	//	 filter: progid:DXImageTransform.Microsoft.Alpha( *opacity=50* )
	//
	private function parseEntitiesAssignment() {

		$key = $this->MatchReg('/\\G\w+(?=\s?=)/');
		if( !$key ){
			return;
		}

		if( !$this->MatchChar('=') ){
			return;
		}

		$value = $this->parseEntity();
		if( $value ){
			return $this->NewObj2('Less_Tree_Assignment',array($key[0], $value));
		}
	}

	//
	// Parse url() tokens
	//
	// We use a specific rule for urls, because they don't really behave like
	// standard function calls. The difference is that the argument doesn't have
	// to be enclosed within a string, so it can't be parsed as an Expression.
	//
	private function parseEntitiesUrl(){


		if( $this->input[$this->pos] !== 'u' || !$this->matchReg('/\\Gurl\(/') ){
			return;
		}

		$value = $this->match( array('parseEntitiesQuoted','parseEntitiesVariable','/\\Gdata\:.*?[^\)]+/','/\\G(?:(?:\\\\[\(\)\'"])|[^\(\)\'"])+/') );
		if( !$value ){
			$value = '';
		}


		$this->expectChar(')');


		if( isset($value->value) || $value instanceof Less_Tree_Variable ){
			return $this->NewObj2('Less_Tree_Url',array($value, $this->env->currentFileInfo));
		}

		return $this->NewObj2('Less_Tree_Url', array( $this->NewObj1('Less_Tree_Anonymous',$value), $this->env->currentFileInfo) );
	}


	//
	// A Variable entity, such as `@fink`, in
	//
	//	 width: @fink + 2px
	//
	// We use a different parser for variable definitions,
	// see `parsers.variable`.
	//
	private function parseEntitiesVariable(){
		$index = $this->pos;
		if ($this->PeekChar('@') && ($name = $this->MatchReg('/\\G@@?[\w-]+/'))) {
			return $this->NewObj3('Less_Tree_Variable', array( $name[0], $index, $this->env->currentFileInfo));
		}
	}


	// A variable entity using the protective {} e.g. @{var}
	private function parseEntitiesVariableCurly() {
		$index = $this->pos;

		if( $this->input_len > ($this->pos+1) && $this->input[$this->pos] === '@' && ($curly = $this->MatchReg('/\\G@\{([\w-]+)\}/')) ){
			return $this->NewObj3('Less_Tree_Variable',array('@'.$curly[1], $index, $this->env->currentFileInfo));
		}
	}

	//
	// A Hexadecimal color
	//
	//	 #4F3C2F
	//
	// `rgb` and `hsl` colors are parsed through the `entities.call` parser.
	//
	private function parseEntitiesColor(){
		if ($this->PeekChar('#') && ($rgb = $this->MatchReg('/\\G#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})/'))) {
			return $this->NewObj1('Less_Tree_Color',$rgb[1]);
		}
	}

	//
	// A Dimension, that is, a number and a unit
	//
	//	 0.5em 95%
	//
	private function parseEntitiesDimension(){

		$c = @ord($this->input[$this->pos]);

		//Is the first char of the dimension 0-9, '.', '+' or '-'
		if (($c > 57 || $c < 43) || $c === 47 || $c == 44){
			return;
		}

		$value = $this->MatchReg('/\\G([+-]?\d*\.?\d+)(%|[a-z]+)?/');
		if( $value ){

			if( isset($value[2]) ){
				return $this->NewObj2('Less_Tree_Dimension', array($value[1],$value[2]));
			}
			return $this->NewObj1('Less_Tree_Dimension',$value[1]);
		}
	}


	//
	// A unicode descriptor, as is used in unicode-range
	//
	// U+0?? or U+00A1-00A9
	//
	function parseUnicodeDescriptor() {
		$ud = $this->MatchReg('/\\G(U\+[0-9a-fA-F?]+)(\-[0-9a-fA-F?]+)?/');
		if( $ud ){
			return $this->NewObj1('Less_Tree_UnicodeDescriptor', $ud[0]);
		}
	}


	//
	// JavaScript code to be evaluated
	//
	//	 `window.location.href`
	//
	private function parseEntitiesJavascript(){
		$e = false;
		$j = $this->pos;
		if( $this->input[$j] === '~' ){
			$j++;
			$e = true;
		}
		if( $this->input[$j] !== '`' ){
			return;
		}
		if( $e ){
			$this->MatchChar('~');
		}
		$str = $this->MatchReg('/\\G`([^`]*)`/');
		if( $str ){
			return $this->NewObj3('Less_Tree_Javascript', array($str[1], $this->pos, $e));
		}
	}


	//
	// The variable part of a variable definition. Used in the `rule` parser
	//
	//	 @fink:
	//
	private function parseVariable(){
		if ($this->PeekChar('@') && ($name = $this->MatchReg('/\\G(@[\w-]+)\s*:/'))) {
			return $name[1];
		}
	}


	//
	// The variable part of a variable definition. Used in the `rule` parser
	//
	// @fink();
	//
	private function parseRulesetCall(){

		if( $this->input[$this->pos] === '@' && ($name = $this->MatchReg('/\\G(@[\w-]+)\s*\(\s*\)\s*;/')) ){
			return $this->NewObj1('Less_Tree_RulesetCall', $name[1] );
		}
	}


	//
	// extend syntax - used to extend selectors
	//
	function parseExtend($isRule = false){

		$index = $this->pos;
		$extendList = array();


		if( !$this->MatchReg( $isRule ? '/\\G&:extend\(/' : '/\\G:extend\(/' ) ){ return; }

		do{
			$option = null;
			$elements = array();
			while( true ){
				$option = $this->MatchReg('/\\G(all)(?=\s*(\)|,))/');
				if( $option ){ break; }
				$e = $this->parseElement();
				if( !$e ){ break; }
				$elements[] = $e;
			}

			if( $option ){
				$option = $option[1];
			}

			$extendList[] = $this->NewObj3('Less_Tree_Extend', array( $this->NewObj1('Less_Tree_Selector',$elements), $option, $index ));

		}while( $this->MatchChar(",") );

		$this->expect('/\\G\)/');

		if( $isRule ){
			$this->expect('/\\G;/');
		}

		return $extendList;
	}


	//
	// A Mixin call, with an optional argument list
	//
	//	 #mixins > .square(#fff);
	//	 .rounded(4px, black);
	//	 .button;
	//
	// The `while` loop is there because mixins can be
	// namespaced, but we only support the child and descendant
	// selector for now.
	//
	private function parseMixinCall(){

		$char = $this->input[$this->pos];
		if( $char !== '.' && $char !== '#' ){
			return;
		}

		$index = $this->pos;
		$this->save(); // stop us absorbing part of an invalid selector

		$elements = $this->parseMixinCallElements();

		if( $elements ){

			if( $this->MatchChar('(') ){
				$returned = $this->parseMixinArgs(true);
				$args = $returned['args'];
				$this->expectChar(')');
			}else{
				$args = array();
			}

			$important = $this->parseImportant();

			if( $this->parseEnd() ){
				$this->forget();
				return $this->NewObj5('Less_Tree_Mixin_Call', array( $elements, $args, $index, $this->env->currentFileInfo, $important));
			}
		}

		$this->restore();
	}


	private function parseMixinCallElements(){
		$elements = array();
		$c = null;

		while( true ){
			$elemIndex = $this->pos;
			$e = $this->MatchReg('/\\G[#.](?:[\w-]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/');
			if( !$e ){
				break;
			}
			$elements[] = $this->NewObj4('Less_Tree_Element', array($c, $e[0], $elemIndex, $this->env->currentFileInfo));
			$c = $this->MatchChar('>');
		}

		return $elements;
	}



	/**
	 * @param boolean $isCall
	 */
	private function parseMixinArgs( $isCall ){
		$expressions = array();
		$argsSemiColon = array();
		$isSemiColonSeperated = null;
		$argsComma = array();
		$expressionContainsNamed = null;
		$name = null;
		$returner = array('args'=>array(), 'variadic'=> false);

		$this->save();

		while( true ){
			if( $isCall ){
				$arg = $this->MatchFuncs( array( 'parseDetachedRuleset','parseExpression' ) );
			} else {
				$this->parseComments();
				if( $this->input[ $this->pos ] === '.' && $this->MatchReg('/\\G\.{3}/') ){
					$returner['variadic'] = true;
					if( $this->MatchChar(";") && !$isSemiColonSeperated ){
						$isSemiColonSeperated = true;
					}

					if( $isSemiColonSeperated ){
						$argsSemiColon[] = array('variadic'=>true);
					}else{
						$argsComma[] = array('variadic'=>true);
					}
					break;
				}
				$arg = $this->MatchFuncs( array('parseEntitiesVariable','parseEntitiesLiteral','parseEntitiesKeyword') );
			}

			if( !$arg ){
				break;
			}


			$nameLoop = null;
			if( $arg instanceof Less_Tree_Expression ){
				$arg->throwAwayComments();
			}
			$value = $arg;
			$val = null;

			if( $isCall ){
				// Variable
				if( property_exists($arg,'value') && count($arg->value) == 1 ){
					$val = $arg->value[0];
				}
			} else {
				$val = $arg;
			}


			if( $val instanceof Less_Tree_Variable ){

				if( $this->MatchChar(':') ){
					if( $expressions ){
						if( $isSemiColonSeperated ){
							$this->Error('Cannot mix ; and , as delimiter types');
						}
						$expressionContainsNamed = true;
					}

					// we do not support setting a ruleset as a default variable - it doesn't make sense
					// However if we do want to add it, there is nothing blocking it, just don't error
					// and remove isCall dependency below
					$value = null;
					if( $isCall ){
						$value = $this->parseDetachedRuleset();
					}
					if( !$value ){
						$value = $this->parseExpression();
					}

					if( !$value ){
						if( $isCall ){
							$this->Error('could not understand value for named argument');
						} else {
							$this->restore();
							$returner['args'] = array();
							return $returner;
						}
					}

					$nameLoop = ($name = $val->name);
				}elseif( !$isCall && $this->MatchReg('/\\G\.{3}/') ){
					$returner['variadic'] = true;
					if( $this->MatchChar(";") && !$isSemiColonSeperated ){
						$isSemiColonSeperated = true;
					}
					if( $isSemiColonSeperated ){
						$argsSemiColon[] = array('name'=> $arg->name, 'variadic' => true);
					}else{
						$argsComma[] = array('name'=> $arg->name, 'variadic' => true);
					}
					break;
				}elseif( !$isCall ){
					$name = $nameLoop = $val->name;
					$value = null;
				}
			}

			if( $value ){
				$expressions[] = $value;
			}

			$argsComma[] = array('name'=>$nameLoop, 'value'=>$value );

			if( $this->MatchChar(',') ){
				continue;
			}

			if( $this->MatchChar(';') || $isSemiColonSeperated ){

				if( $expressionContainsNamed ){
					$this->Error('Cannot mix ; and , as delimiter types');
				}

				$isSemiColonSeperated = true;

				if( count($expressions) > 1 ){
					$value = $this->NewObj1('Less_Tree_Value', $expressions);
				}
				$argsSemiColon[] = array('name'=>$name, 'value'=>$value );

				$name = null;
				$expressions = array();
				$expressionContainsNamed = false;
			}
		}

		$this->forget();
		$returner['args'] = ($isSemiColonSeperated ? $argsSemiColon : $argsComma);
		return $returner;
	}



	//
	// A Mixin definition, with a list of parameters
	//
	//	 .rounded (@radius: 2px, @color) {
	//		...
	//	 }
	//
	// Until we have a finer grained state-machine, we have to
	// do a look-ahead, to make sure we don't have a mixin call.
	// See the `rule` function for more information.
	//
	// We start by matching `.rounded (`, and then proceed on to
	// the argument list, which has optional default values.
	// We store the parameters in `params`, with a `value` key,
	// if there is a value, such as in the case of `@radius`.
	//
	// Once we've got our params list, and a closing `)`, we parse
	// the `{...}` block.
	//
	private function parseMixinDefinition(){
		$cond = null;

		$char = $this->input[$this->pos];
		if( ($char !== '.' && $char !== '#') || ($char === '{' && $this->PeekReg('/\\G[^{]*\}/')) ){
			return;
		}

		$this->save();

		$match = $this->MatchReg('/\\G([#.](?:[\w-]|\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+)\s*\(/');
		if( $match ){
			$name = $match[1];

			$argInfo = $this->parseMixinArgs( false );
			$params = $argInfo['args'];
			$variadic = $argInfo['variadic'];


			// .mixincall("@{a}");
			// looks a bit like a mixin definition..
			// also
			// .mixincall(@a: {rule: set;});
			// so we have to be nice and restore
			if( !$this->MatchChar(')') ){
				$this->furthest = $this->pos;
				$this->restore();
				return;
			}


			$this->parseComments();

			if ($this->MatchReg('/\\Gwhen/')) { // Guard
				$cond = $this->expect('parseConditions', 'Expected conditions');
			}

			$ruleset = $this->parseBlock();

			if( is_array($ruleset) ){
				$this->forget();
				return $this->NewObj5('Less_Tree_Mixin_Definition', array( $name, $params, $ruleset, $cond, $variadic));
			}

			$this->restore();
		}else{
			$this->forget();
		}
	}

	//
	// Entities are the smallest recognized token,
	// and can be found inside a rule's value.
	//
	private function parseEntity(){

		return $this->MatchFuncs( array('parseEntitiesLiteral','parseEntitiesVariable','parseEntitiesUrl','parseEntitiesCall','parseEntitiesKeyword','parseEntitiesJavascript','parseComment') );
	}

	//
	// A Rule terminator. Note that we use `peek()` to check for '}',
	// because the `block` rule will be expecting it, but we still need to make sure
	// it's there, if ';' was omitted.
	//
	private function parseEnd(){
		return $this->MatchChar(';') || $this->PeekChar('}');
	}

	//
	// IE's alpha function
	//
	//	 alpha(opacity=88)
	//
	private function parseAlpha(){

		if ( ! $this->MatchReg('/\\G\(opacity=/i')) {
			return;
		}

		$value = $this->MatchReg('/\\G[0-9]+/');
		if( $value ){
			$value = $value[0];
		}else{
			$value = $this->parseEntitiesVariable();
			if( !$value ){
				return;
			}
		}

		$this->expectChar(')');
		return $this->NewObj1('Less_Tree_Alpha',$value);
	}


	//
	// A Selector Element
	//
	//	 div
	//	 + h1
	//	 #socks
	//	 input[type="text"]
	//
	// Elements are the building blocks for Selectors,
	// they are made out of a `Combinator` (see combinator rule),
	// and an element name, such as a tag a class, or `*`.
	//
	private function parseElement(){
		$c = $this->parseCombinator();
		$index = $this->pos;

		$e = $this->match( array('/\\G(?:\d+\.\d+|\d+)%/', '/\\G(?:[.#]?|:*)(?:[\w-]|[^\x00-\x9f]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/',
			'#*', '#&', 'parseAttribute', '/\\G\([^()@]+\)/', '/\\G[\.#](?=@)/', 'parseEntitiesVariableCurly') );

		if( is_null($e) ){
			$this->save();
			if( $this->MatchChar('(') ){
				if( ($v = $this->parseSelector()) && $this->MatchChar(')') ){
					$e = $this->NewObj1('Less_Tree_Paren',$v);
					$this->forget();
				}else{
					$this->restore();
				}
			}else{
				$this->forget();
			}
		}

		if( !is_null($e) ){
			return $this->NewObj4('Less_Tree_Element',array( $c, $e, $index, $this->env->currentFileInfo));
		}
	}

	//
	// Combinators combine elements together, in a Selector.
	//
	// Because our parser isn't white-space sensitive, special care
	// has to be taken, when parsing the descendant combinator, ` `,
	// as it's an empty space. We have to check the previous character
	// in the input, to see if it's a ` ` character.
	//
	private function parseCombinator(){
		if( $this->pos < $this->input_len ){
			$c = $this->input[$this->pos];
			if ($c === '>' || $c === '+' || $c === '~' || $c === '|' || $c === '^' ){

				$this->pos++;
				if( $this->input[$this->pos] === '^' ){
					$c = '^^';
					$this->pos++;
				}

				$this->skipWhitespace(0);

				return $c;
			}

			if( $this->pos > 0 && $this->isWhitespace(-1) ){
				return ' ';
			}
		}
	}

	//
	// A CSS selector (see selector below)
	// with less extensions e.g. the ability to extend and guard
	//
	private function parseLessSelector(){
		return $this->parseSelector(true);
	}

	//
	// A CSS Selector
	//
	//	 .class > div + h1
	//	 li a:hover
	//
	// Selectors are made out of one or more Elements, see above.
	//
	private function parseSelector( $isLess = false ){
		$elements = array();
		$extendList = array();
		$condition = null;
		$when = false;
		$extend = false;
		$e = null;
		$c = null;
		$index = $this->pos;

		while( ($isLess && ($extend = $this->parseExtend())) || ($isLess && ($when = $this->MatchReg('/\\Gwhen/') )) || ($e = $this->parseElement()) ){
			if( $when ){
				$condition = $this->expect('parseConditions', 'expected condition');
			}elseif( $condition ){
				//error("CSS guard can only be used at the end of selector");
			}elseif( $extend ){
				$extendList = array_merge($extendList,$extend);
			}else{
				//if( count($extendList) ){
				//error("Extend can only be used at the end of selector");
				//}
				if( $this->pos < $this->input_len ){
					$c = $this->input[ $this->pos ];
				}
				$elements[] = $e;
				$e = null;
			}

			if( $c === '{' || $c === '}' || $c === ';' || $c === ',' || $c === ')') { break; }
		}

		if( $elements ){
			return $this->NewObj5('Less_Tree_Selector',array($elements, $extendList, $condition, $index, $this->env->currentFileInfo));
		}
		if( $extendList ) {
			$this->Error('Extend must be used to extend a selector, it cannot be used on its own');
		}
	}

	private function parseTag(){
		return ( $tag = $this->MatchReg('/\\G[A-Za-z][A-Za-z-]*[0-9]?/') ) ? $tag : $this->MatchChar('*');
	}

	private function parseAttribute(){

		$val = null;

		if( !$this->MatchChar('[') ){
			return;
		}

		$key = $this->parseEntitiesVariableCurly();
		if( !$key ){
			$key = $this->expect('/\\G(?:[_A-Za-z0-9-\*]*\|)?(?:[_A-Za-z0-9-]|\\\\.)+/');
		}

		$op = $this->MatchReg('/\\G[|~*$^]?=/');
		if( $op ){
			$val = $this->match( array('parseEntitiesQuoted','/\\G[0-9]+%/','/\\G[\w-]+/','parseEntitiesVariableCurly') );
		}

		$this->expectChar(']');

		return $this->NewObj3('Less_Tree_Attribute',array( $key, $op[0], $val));
	}

	//
	// The `block` rule is used by `ruleset` and `mixin.definition`.
	// It's a wrapper around the `primary` rule, with added `{}`.
	//
	private function parseBlock(){
		if( $this->MatchChar('{') ){
			$content = $this->parsePrimary();
			if( $this->MatchChar('}') ){
				return $content;
			}
		}
	}

	private function parseBlockRuleset(){
		$block = $this->parseBlock();

		if( $block ){
			$block = $this->NewObj2('Less_Tree_Ruleset',array( null, $block));
		}

		return $block;
	}

	private function parseDetachedRuleset(){
		$blockRuleset = $this->parseBlockRuleset();
		if( $blockRuleset ){
			return $this->NewObj1('Less_Tree_DetachedRuleset',$blockRuleset);
		}
	}

	//
	// div, .class, body > p {...}
	//
	private function parseRuleset(){
		$selectors = array();

		$this->save();

		while( true ){
			$s = $this->parseLessSelector();
			if( !$s ){
				break;
			}
			$selectors[] = $s;
			$this->parseComments();

			if( $s->condition && count($selectors) > 1 ){
				$this->Error('Guards are only currently allowed on a single selector.');
			}

			if( !$this->MatchChar(',') ){
				break;
			}
			if( $s->condition ){
				$this->Error('Guards are only currently allowed on a single selector.');
			}
			$this->parseComments();
		}


		if( $selectors ){
			$rules = $this->parseBlock();
			if( is_array($rules) ){
				$this->forget();
				return $this->NewObj2('Less_Tree_Ruleset',array( $selectors, $rules)); //Less_Environment::$strictImports
			}
		}

		// Backtrack
		$this->furthest = $this->pos;
		$this->restore();
	}

	/**
	 * Custom less.php parse function for finding simple name-value css pairs
	 * ex: width:100px;
	 *
	 */
	private function parseNameValue(){

		$index = $this->pos;
		$this->save();


		//$match = $this->MatchReg('/\\G([a-zA-Z\-]+)\s*:\s*((?:\'")?[a-zA-Z0-9\-% \.,!]+?(?:\'")?)\s*([;}])/');
		$match = $this->MatchReg('/\\G([a-zA-Z\-]+)\s*:\s*([\'"]?[#a-zA-Z0-9\-%\.,]+?[\'"]?) *(! *important)?\s*([;}])/');
		if( $match ){

			if( $match[4] == '}' ){
				$this->pos = $index + strlen($match[0])-1;
			}

			if( $match[3] ){
				$match[2] .= ' !important';
			}

			return $this->NewObj4('Less_Tree_NameValue',array( $match[1], $match[2], $index, $this->env->currentFileInfo));
		}

		$this->restore();
	}


	private function parseRule( $tryAnonymous = null ){

		$merge = false;
		$startOfRule = $this->pos;

		$c = $this->input[$this->pos];
		if( $c === '.' || $c === '#' || $c === '&' ){
			return;
		}

		$this->save();
		$name = $this->MatchFuncs( array('parseVariable','parseRuleProperty'));

		if( $name ){

			$isVariable = is_string($name);

			$value = null;
			if( $isVariable ){
				$value = $this->parseDetachedRuleset();
			}

			$important = null;
			if( !$value ){

				// prefer to try to parse first if its a variable or we are compressing
				// but always fallback on the other one
				//if( !$tryAnonymous && is_string($name) && $name[0] === '@' ){
				if( !$tryAnonymous && (Less_Parser::$options['compress'] || $isVariable) ){
					$value = $this->MatchFuncs( array('parseValue','parseAnonymousValue'));
				}else{
					$value = $this->MatchFuncs( array('parseAnonymousValue','parseValue'));
				}

				$important = $this->parseImportant();

				// a name returned by this.ruleProperty() is always an array of the form:
				// [string-1, ..., string-n, ""] or [string-1, ..., string-n, "+"]
				// where each item is a tree.Keyword or tree.Variable
				if( !$isVariable && is_array($name) ){
					$nm = array_pop($name);
					if( $nm->value ){
						$merge = $nm->value;
					}
				}
			}


			if( $value && $this->parseEnd() ){
				$this->forget();
				return $this->NewObj6('Less_Tree_Rule',array( $name, $value, $important, $merge, $startOfRule, $this->env->currentFileInfo));
			}else{
				$this->furthest = $this->pos;
				$this->restore();
				if( $value && !$tryAnonymous ){
					return $this->parseRule(true);
				}
			}
		}else{
			$this->forget();
		}
	}

	function parseAnonymousValue(){

		if( preg_match('/\\G([^@+\/\'"*`(;{}-]*);/',$this->input, $match, 0, $this->pos) ){
			$this->pos += strlen($match[1]);
			return $this->NewObj1('Less_Tree_Anonymous',$match[1]);
		}
	}

	//
	// An @import directive
	//
	//	 @import "lib";
	//
	// Depending on our environment, importing is done differently:
	// In the browser, it's an XHR request, in Node, it would be a
	// file-system operation. The function used for importing is
	// stored in `import`, which we pass to the Import constructor.
	//
	private function parseImport(){

		$this->save();

		$dir = $this->MatchReg('/\\G@import?\s+/');

		if( $dir ){
			$options = $this->parseImportOptions();
			$path = $this->MatchFuncs( array('parseEntitiesQuoted','parseEntitiesUrl'));

			if( $path ){
				$features = $this->parseMediaFeatures();
				if( $this->MatchChar(';') ){
					if( $features ){
						$features = $this->NewObj1('Less_Tree_Value',$features);
					}

					$this->forget();
					return $this->NewObj5('Less_Tree_Import',array( $path, $features, $options, $this->pos, $this->env->currentFileInfo));
				}
			}
		}

		$this->restore();
	}

	private function parseImportOptions(){

		$options = array();

		// list of options, surrounded by parens
		if( !$this->MatchChar('(') ){
			return $options;
		}
		do{
			$optionName = $this->parseImportOption();
			if( $optionName ){
				$value = true;
				switch( $optionName ){
					case "css":
						$optionName = "less";
						$value = false;
						break;
					case "once":
						$optionName = "multiple";
						$value = false;
						break;
				}
				$options[$optionName] = $value;
				if( !$this->MatchChar(',') ){ break; }
			}
		}while( $optionName );
		$this->expectChar(')');
		return $options;
	}

	private function parseImportOption(){
		$opt = $this->MatchReg('/\\G(less|css|multiple|once|inline|reference|optional)/');
		if( $opt ){
			return $opt[1];
		}
	}

	private function parseMediaFeature() {
		$nodes = array();

		do{
			$e = $this->MatchFuncs(array('parseEntitiesKeyword','parseEntitiesVariable'));
			if( $e ){
				$nodes[] = $e;
			} elseif ($this->MatchChar('(')) {
				$p = $this->parseProperty();
				$e = $this->parseValue();
				if ($this->MatchChar(')')) {
					if ($p && $e) {
						$r = $this->NewObj7('Less_Tree_Rule', array( $p, $e, null, null, $this->pos, $this->env->currentFileInfo, true));
						$nodes[] = $this->NewObj1('Less_Tree_Paren',$r);
					} elseif ($e) {
						$nodes[] = $this->NewObj1('Less_Tree_Paren',$e);
					} else {
						return null;
					}
				} else
					return null;
			}
		} while ($e);

		if ($nodes) {
			return $this->NewObj1('Less_Tree_Expression',$nodes);
		}
	}

	private function parseMediaFeatures() {
		$features = array();

		do{
			$e = $this->parseMediaFeature();
			if( $e ){
				$features[] = $e;
				if (!$this->MatchChar(',')) break;
			}else{
				$e = $this->parseEntitiesVariable();
				if( $e ){
					$features[] = $e;
					if (!$this->MatchChar(',')) break;
				}
			}
		} while ($e);

		return $features ? $features : null;
	}

	private function parseMedia() {
		if( $this->MatchReg('/\\G@media/') ){
			$features = $this->parseMediaFeatures();
			$rules = $this->parseBlock();

			if( is_array($rules) ){
				return $this->NewObj4('Less_Tree_Media',array( $rules, $features, $this->pos, $this->env->currentFileInfo));
			}
		}
	}


	//
	// A CSS Directive
	//
	// @charset "utf-8";
	//
	private function parseDirective(){

		if( !$this->PeekChar('@') ){
			return;
		}

		$rules = null;
		$index = $this->pos;
		$hasBlock = true;
		$hasIdentifier = false;
		$hasExpression = false;
		$hasUnknown = false;


		$value = $this->MatchFuncs(array('parseImport','parseMedia'));
		if( $value ){
			return $value;
		}

		$this->save();

		$name = $this->MatchReg('/\\G@[a-z-]+/');

		if( !$name ) return;
		$name = $name[0];


		$nonVendorSpecificName = $name;
		$pos = strpos($name,'-', 2);
		if( $name[1] == '-' && $pos > 0 ){
			$nonVendorSpecificName = "@" . substr($name, $pos + 1);
		}


		switch( $nonVendorSpecificName ){
			/*
			case "@font-face":
			case "@viewport":
			case "@top-left":
			case "@top-left-corner":
			case "@top-center":
			case "@top-right":
			case "@top-right-corner":
			case "@bottom-left":
			case "@bottom-left-corner":
			case "@bottom-center":
			case "@bottom-right":
			case "@bottom-right-corner":
			case "@left-top":
			case "@left-middle":
			case "@left-bottom":
			case "@right-top":
			case "@right-middle":
			case "@right-bottom":
			hasBlock = true;
			break;
			*/
			case "@charset":
				$hasIdentifier = true;
				$hasBlock = false;
				break;
			case "@namespace":
				$hasExpression = true;
				$hasBlock = false;
				break;
			case "@keyframes":
				$hasIdentifier = true;
				break;
			case "@host":
			case "@page":
			case "@document":
			case "@supports":
				$hasUnknown = true;
				break;
		}

		if( $hasIdentifier ){
			$value = $this->parseEntity();
			if( !$value ){
				$this->error("expected " . $name . " identifier");
			}
		} else if( $hasExpression ){
			$value = $this->parseExpression();
			if( !$value ){
				$this->error("expected " . $name. " expression");
			}
		} else if ($hasUnknown) {

			$value = $this->MatchReg('/\\G[^{;]+/');
			if( $value ){
				$value = $this->NewObj1('Less_Tree_Anonymous',trim($value[0]));
			}
		}

		if( $hasBlock ){
			$rules = $this->parseBlockRuleset();
		}

		if( $rules || (!$hasBlock && $value && $this->MatchChar(';'))) {
			$this->forget();
			return $this->NewObj5('Less_Tree_Directive',array($name, $value, $rules, $index, $this->env->currentFileInfo));
		}

		$this->restore();
	}


	//
	// A Value is a comma-delimited list of Expressions
	//
	//	 font-family: Baskerville, Georgia, serif;
	//
	// In a Rule, a Value represents everything after the `:`,
	// and before the `;`.
	//
	private function parseValue(){
		$expressions = array();

		do{
			$e = $this->parseExpression();
			if( $e ){
				$expressions[] = $e;
				if (! $this->MatchChar(',')) {
					break;
				}
			}
		}while($e);

		if( $expressions ){
			return $this->NewObj1('Less_Tree_Value',$expressions);
		}
	}

	private function parseImportant (){
		if( $this->PeekChar('!') && $this->MatchReg('/\\G! *important/') ){
			return ' !important';
		}
	}

	private function parseSub (){

		if( $this->MatchChar('(') ){
			$a = $this->parseAddition();
			if( $a ){
				$this->expectChar(')');
				return $this->NewObj2('Less_Tree_Expression',array( array($a), true) ); //instead of $e->parens = true so the value is cached
			}
		}
	}


	/**
	 * Parses multiplication operation
	 *
	 * @return Less_Tree_Operation|null
	 */
	function parseMultiplication(){

		$return = $m = $this->parseOperand();
		if( $return ){
			while( true ){

				$isSpaced = $this->isWhitespace( -1 );

				if( $this->PeekReg('/\\G\/[*\/]/') ){
					break;
				}

				$op = $this->MatchChar('/');
				if( !$op ){
					$op = $this->MatchChar('*');
					if( !$op ){
						break;
					}
				}

				$a = $this->parseOperand();

				if(!$a) { break; }

				$m->parensInOp = true;
				$a->parensInOp = true;
				$return = $this->NewObj3('Less_Tree_Operation',array( $op, array( $return, $a ), $isSpaced) );
			}
		}
		return $return;

	}


	/**
	 * Parses an addition operation
	 *
	 * @return Less_Tree_Operation|null
	 */
	private function parseAddition (){

		$return = $m = $this->parseMultiplication();
		if( $return ){
			while( true ){

				$isSpaced = $this->isWhitespace( -1 );

				$op = $this->MatchReg('/\\G[-+]\s+/');
				if( $op ){
					$op = $op[0];
				}else{
					if( !$isSpaced ){
						$op = $this->match(array('#+','#-'));
					}
					if( !$op ){
						break;
					}
				}

				$a = $this->parseMultiplication();
				if( !$a ){
					break;
				}

				$m->parensInOp = true;
				$a->parensInOp = true;
				$return = $this->NewObj3('Less_Tree_Operation',array($op, array($return, $a), $isSpaced));
			}
		}

		return $return;
	}


	/**
	 * Parses the conditions
	 *
	 * @return Less_Tree_Condition|null
	 */
	private function parseConditions() {
		$index = $this->pos;
		$return = $a = $this->parseCondition();
		if( $a ){
			while( true ){
				if( !$this->PeekReg('/\\G,\s*(not\s*)?\(/') ||  !$this->MatchChar(',') ){
					break;
				}
				$b = $this->parseCondition();
				if( !$b ){
					break;
				}

				$return = $this->NewObj4('Less_Tree_Condition',array('or', $return, $b, $index));
			}
			return $return;
		}
	}

	private function parseCondition() {
		$index = $this->pos;
		$negate = false;
		$c = null;

		if ($this->MatchReg('/\\Gnot/')) $negate = true;
		$this->expectChar('(');
		$a = $this->MatchFuncs(array('parseAddition','parseEntitiesKeyword','parseEntitiesQuoted'));

		if( $a ){
			$op = $this->MatchReg('/\\G(?:>=|<=|=<|[<=>])/');
			if( $op ){
				$b = $this->MatchFuncs(array('parseAddition','parseEntitiesKeyword','parseEntitiesQuoted'));
				if( $b ){
					$c = $this->NewObj5('Less_Tree_Condition',array($op[0], $a, $b, $index, $negate));
				} else {
					$this->Error('Unexpected expression');
				}
			} else {
				$k = $this->NewObj1('Less_Tree_Keyword','true');
				$c = $this->NewObj5('Less_Tree_Condition',array('=', $a, $k, $index, $negate));
			}
			$this->expectChar(')');
			return $this->MatchReg('/\\Gand/') ? $this->NewObj3('Less_Tree_Condition',array('and', $c, $this->parseCondition())) : $c;
		}
	}

	/**
	 * An operand is anything that can be part of an operation,
	 * such as a Color, or a Variable
	 *
	 */
	private function parseOperand (){

		$negate = false;
		$offset = $this->pos+1;
		if( $offset >= $this->input_len ){
			return;
		}
		$char = $this->input[$offset];
		if( $char === '@' || $char === '(' ){
			$negate = $this->MatchChar('-');
		}

		$o = $this->MatchFuncs(array('parseSub','parseEntitiesDimension','parseEntitiesColor','parseEntitiesVariable','parseEntitiesCall'));

		if( $negate ){
			$o->parensInOp = true;
			$o = $this->NewObj1('Less_Tree_Negative',$o);
		}

		return $o;
	}


	/**
	 * Expressions either represent mathematical operations,
	 * or white-space delimited Entities.
	 *
	 *	 1px solid black
	 *	 @var * 2
	 *
	 * @return Less_Tree_Expression|null
	 */
	private function parseExpression (){
		$entities = array();

		do{
			$e = $this->MatchFuncs(array('parseAddition','parseEntity'));
			if( $e ){
				$entities[] = $e;
				// operations do not allow keyword "/" dimension (e.g. small/20px) so we support that here
				if( !$this->PeekReg('/\\G\/[\/*]/') ){
					$delim = $this->MatchChar('/');
					if( $delim ){
						$entities[] = $this->NewObj1('Less_Tree_Anonymous',$delim);
					}
				}
			}
		}while($e);

		if( $entities ){
			return $this->NewObj1('Less_Tree_Expression',$entities);
		}
	}


	/**
	 * Parse a property
	 * eg: 'min-width', 'orientation', etc
	 *
	 * @return string
	 */
	private function parseProperty (){
		$name = $this->MatchReg('/\\G(\*?-?[_a-zA-Z0-9-]+)\s*:/');
		if( $name ){
			return $name[1];
		}
	}


	/**
	 * Parse a rule property
	 * eg: 'color', 'width', 'height', etc
	 *
	 * @return string
	 */
	private function parseRuleProperty(){
		$offset = $this->pos;
		$name = array();
		$index = array();
		$length = 0;


		$this->rulePropertyMatch('/\\G(\*?)/', $offset, $length, $index, $name );
		while( $this->rulePropertyMatch('/\\G((?:[\w-]+)|(?:@\{[\w-]+\}))/', $offset, $length, $index, $name )); // !

		if( (count($name) > 1) && $this->rulePropertyMatch('/\\G\s*((?:\+_|\+)?)\s*:/', $offset, $length, $index, $name) ){
			// at last, we have the complete match now. move forward,
			// convert name particles to tree objects and return:
			$this->skipWhitespace($length);

			if( $name[0] === '' ){
				array_shift($name);
				array_shift($index);
			}
			foreach($name as $k => $s ){
				if( !$s || $s[0] !== '@' ){
					$name[$k] = $this->NewObj1('Less_Tree_Keyword',$s);
				}else{
					$name[$k] = $this->NewObj3('Less_Tree_Variable',array('@' . substr($s,2,-1), $index[$k], $this->env->currentFileInfo));
				}
			}
			return $name;
		}


	}

	private function rulePropertyMatch( $re, &$offset, &$length,  &$index, &$name ){
		preg_match($re, $this->input, $a, 0, $offset);
		if( $a ){
			$index[] = $this->pos + $length;
			$length += strlen($a[0]);
			$offset += strlen($a[0]);
			$name[] = $a[1];
			return true;
		}
	}

	public static function serializeVars( $vars ){
		$s = '';

		foreach($vars as $name => $value){
			$s .= (($name[0] === '@') ? '' : '@') . $name .': '. $value . ((substr($value,-1) === ';') ? '' : ';');
		}

		return $s;
	}


	/**
	 * Some versions of php have trouble with method_exists($a,$b) if $a is not an object
	 *
	 * @param string $b
	 */
	public static function is_method($a,$b){
		return is_object($a) && method_exists($a,$b);
	}


	/**
	 * Round numbers similarly to javascript
	 * eg: 1.499999 to 1 instead of 2
	 *
	 */
	public static function round($i, $precision = 0){

		$precision = pow(10,$precision);
		$i = $i*$precision;

		$ceil = ceil($i);
		$floor = floor($i);
		if( ($ceil - $i) <= ($i - $floor) ){
			return $ceil/$precision;
		}else{
			return $floor/$precision;
		}
	}


	/**
	 * Create Less_Tree_* objects and optionally generate a cache string
	 *
	 * @return mixed
	 */
	public function NewObj0($class){
		$obj = new $class();
		if( $this->CacheEnabled() ){
			$obj->cache_string = ' new '.$class.'()';
		}
		return $obj;
	}

	public function NewObj1($class, $arg){
		$obj = new $class( $arg );
		if( $this->CacheEnabled() ){
			$obj->cache_string = ' new '.$class.'('.Less_Parser::ArgString($arg).')';
		}
		return $obj;
	}

	public function NewObj2($class, $args){
		$obj = new $class( $args[0], $args[1] );
		if( $this->CacheEnabled() ){
			$this->ObjCache( $obj, $class, $args);
		}
		return $obj;
	}

	public function NewObj3($class, $args){
		$obj = new $class( $args[0], $args[1], $args[2] );
		if( $this->CacheEnabled() ){
			$this->ObjCache( $obj, $class, $args);
		}
		return $obj;
	}

	public function NewObj4($class, $args){
		$obj = new $class( $args[0], $args[1], $args[2], $args[3] );
		if( $this->CacheEnabled() ){
			$this->ObjCache( $obj, $class, $args);
		}
		return $obj;
	}

	public function NewObj5($class, $args){
		$obj = new $class( $args[0], $args[1], $args[2], $args[3], $args[4] );
		if( $this->CacheEnabled() ){
			$this->ObjCache( $obj, $class, $args);
		}
		return $obj;
	}

	public function NewObj6($class, $args){
		$obj = new $class( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5] );
		if( $this->CacheEnabled() ){
			$this->ObjCache( $obj, $class, $args);
		}
		return $obj;
	}

	public function NewObj7($class, $args){
		$obj = new $class( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6] );
		if( $this->CacheEnabled() ){
			$this->ObjCache( $obj, $class, $args);
		}
		return $obj;
	}

	//caching
	public function ObjCache($obj, $class, $args=array()){
		$obj->cache_string = ' new '.$class.'('. self::ArgCache($args).')';
	}

	public function ArgCache($args){
		return implode(',',array_map( array('Less_Parser','ArgString'),$args));
	}


	/**
	 * Convert an argument to a string for use in the parser cache
	 *
	 * @return string
	 */
	public static function ArgString($arg){

		$type = gettype($arg);

		if( $type === 'object'){
			$string = $arg->cache_string;
			unset($arg->cache_string);
			return $string;

		}elseif( $type === 'array' ){
			$string = ' Array(';
			foreach($arg as $k => $a){
				$string .= var_export($k,true).' => '.self::ArgString($a).',';
			}
			return $string . ')';
		}

		return var_export($arg,true);
	}

	public function Error($msg){
		throw new Less_Exception_Parser($msg, null, $this->furthest, $this->env->currentFileInfo);
	}

	public static function WinPath($path){
		return str_replace('\\', '/', $path);
	}

	public static function AbsPath($path, $winPath = false){
		if (strpos($path, '//') !== false && preg_match('_^(https?:)?//\\w+(\\.\\w+)+/\\w+_i', $path)) {
			return $winPath ? '' : false;
		} else {
			$path = realpath($path);
			if ($winPath) {
				$path = self::WinPath($path);
			}
			return $path;
		}
	}

	public function CacheEnabled(){
		return (Less_Parser::$options['cache_method'] && (Less_Cache::$cache_dir || (Less_Parser::$options['cache_method'] == 'callback')));
	}

}
