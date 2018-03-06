<?php

namespace Heroku\Buildpack\PHP;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;

class ComposerInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
	const CONF_D_PATHNAME = 'etc/php/conf.d';
	
	protected $composer;
	protected $io;
	protected $ops;
	
	protected $profileCounter = 10;
	protected $configCounter = 10;
	
	protected $allPlatformRequirements = null;
	
	public function activate(Composer $composer, IOInterface $io)
	{
		$this->composer = $composer;
		$this->io = $io;
		
		// check if there already are scripts in .profile.d, or INI files (because we got invoked before), then calculate new starting point for file names
		foreach([
			'profileCounter' => (getenv('profile_dir_path')?:'/dev/null').'/[0-9][0-9][0-9]-*.sh',
			'configCounter' => self::CONF_D_PATHNAME.'/[0-9][0-9][0-9]-*.ini'
		] as $var => $glob) {
			if($matches = glob($glob)) {
				$this->$var = ceil(max(array_merge([$this->$var], array_map(function($e) { return explode('-', pathinfo($e, PATHINFO_FILENAME), 2)[0]; }, $matches)))/10)+1;
			}
		}
		
		$composer->getDownloadManager()->setDownloader(
			'heroku-sys-tar',
			new Downloader(
				$io,
				$composer->getConfig(),
				$composer->getEventDispatcher()
				// $cache
			)
		);
		$composer->getInstallationManager()->addInstaller(new ComposerInstaller($io, $composer));
	}
	
	public static function getSubscribedEvents()
	{
		return [PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall'];
	}

	public function onPostPackageInstall(PackageEvent $event)
	{
		if(!in_array($event->getOperation()->getPackage()->getType(), ['heroku-sys-php', 'heroku-sys-hhvm', 'heroku-sys-php-extension', 'heroku-sys-hhvm-extension', 'heroku-sys-webserver', 'heroku-sys-library'])) return;
		
		$this->initAllPlatformRequirements($event->getOperations());
		
		try {
			$this->configurePackage($event->getOperation()->getPackage());
			$this->enableReplaces($event->getOperation()->getPackage());
			$this->writeProfile($event->getOperation()->getPackage());
			$this->writeExport($event->getOperation()->getPackage());
		} catch(\Exception $e) {
			$this->io->writeError(sprintf('<error>Failed to activate package %s</error>', $event->getOperation()->getPackage()->getName()));
			$this->io->writeError('');
			throw $e;
		}
	}
	
	protected function initAllPlatformRequirements(array $operations)
	{
		if($this->allPlatformRequirements !== null) return;
		
		$this->allPlatformRequirements = [];
		foreach($operations as $operation) {
			foreach($operation->getPackage()->getRequires() as $require) {
				if(strpos($require->getTarget(), 'heroku-sys/') === 0) {
					$this->allPlatformRequirements[$require->getTarget()] = $require->getSource();
				}
			}
		}
	}
	
	protected function configurePackage(PackageInterface $package)
	{
		if(in_array($package->getType(), ['heroku-sys-php-extension', 'heroku-sys-hhvm-extension'])) {
			$this->enableExtension($package->getPrettyName(), $package);
		}
	}
	
	protected function enableReplaces(PackageInterface $package)
	{
		// we may "replace" any of the packages (e.g. ext-mbstring is bundled with PHP) that have been required
		// figure out which those are so we can decide if they need enabling (because they're built shared)
		$enable = array_intersect_key($package->getReplaces(), $this->allPlatformRequirements);
		
		foreach(array_keys($enable) as $extension) {
			$this->enableExtension($extension, $package);
		}
	}
	
	protected function enableExtension($prettyName, PackageInterface $parent)
	{
		// for comparison etc
		$packageName = strtolower($prettyName);
		// strip "heroku-sys/ext-"
		$extName = substr($packageName, 15);
		
		// check if it's an extension
		if(strpos($packageName, 'heroku-sys/ext-') !== 0) return;
		
		$extra = $parent->getExtra();
		
		if($parent->getName() == $packageName) {
			// we're enabling the parent package itself
			$config = isset($extra['config']) ? $extra['config'] : true;
		} else {
			// we're enabling another extension that this package provides
			$shared = isset($extra['shared']) ? array_change_key_case($extra['shared'], CASE_LOWER) : [];
			if(!isset($shared[$packageName])) {
				// that ext is on by default or whatever
				return;
			}
			
			$this->io->writeError(sprintf('  - Enabling <info>%s</info> (bundled with <comment>%s</comment>)', $prettyName, $parent->getPrettyName()));
			$this->io->writeError('');
			
			$config = $shared[$packageName];
		}
		
		$ini = sprintf('%s/%03u-%%s.ini', self::CONF_D_PATHNAME, $this->configCounter++*10);
		@mkdir(dirname($ini), 0777, true);
		
		if($config === true || (is_string($config) && substr($config, -3) === '.so' && is_readable($config))) {
			// just enable that ext (arg is true, or the .so filename)
			file_put_contents(sprintf($ini, "ext-$extName"), sprintf("extension=%s\n", $config === true ? "$extName.so" : $config));
		} elseif(is_string($config) && is_readable($config)) {
			// ini file, maybe with special contents like extra config or different .so name (think "zend-opcache" vs "opcache.so")
			// FIXME: consider ignoring/overriding the numeric prefix and re-using the original file name for "replace"d extensions, which may deliberately be different to ensure a certain loading order?
			// example: some rare extensions (e.g. recode: http://php.net/manual/en/recode.installation.php) need to be loaded in a specific order (https://www.pingle.org/2006/10/18/php-crashes-extensions)
			// this can only happen if several extensions, built as shared, are included in a package and will be activated (so typically just PHP with its shared exts); for real dependencies (ext-apc needs ext-apcu, ext-foobar needs ext-mysql), Composer already does that ordering for us
			rename($config, sprintf($ini, "ext-$extName"));
		} elseif (!$config) {
			return;
		} else {
			throw new \RuntimeException('Package declares invalid or missing "config" in "extra"');
		}
	}
	
	protected function writeExport(PackageInterface $package)
	{
		if(!($fn = getenv('export_file_path'))) return;
		
		$extra = $package->getExtra();
		if(!isset($extra['export']) || !$extra['export']) return;
		
		if(is_string($extra['export']) && is_readable($extra['export'])) {
			// a file from the package used to export vars for the next buildpack, e.g. $PATH
			$export = file_get_contents($extra['export']);
			@unlink($extra['export']);
		} elseif(is_array($extra['export'])) {
			// a hash of vars for the next buildpack, e.g. "PATH": "$HOME/.heroku/php/bin:$PATH"
			$export = implode("\n", array_map(function($v, $k) { return sprintf('export %s="%s"', $k, $v); }, $extra['export'], array_keys($extra['export'])));
		} else {
			throw new \RuntimeException('Package declares invalid or missing "export" in "extra"');
		}
		
		file_put_contents($fn, "\n$export\n", FILE_APPEND);
	}
	
	protected function writeProfile(PackageInterface $package)
	{
		if(!getenv('profile_dir_path')) return;
		
		$profile = $package->getExtra();
		if(!isset($profile['profile']) || !$profile['profile']) return;
		$profile = $profile['profile'];
		
		$fn = sprintf("%s/%03u-%s.sh", getenv('profile_dir_path'), $this->profileCounter++*10, str_replace('heroku-sys/', '', $package->getName()));
		@mkdir(dirname($fn), 0777, true);
		
		if(is_string($profile) && is_readable($profile)) {
			// move profile file to ~/.profile.d/
			rename($profile, $fn);
		} elseif(is_array($profile)) {
			// a hash of vars for startup, e.g. "PATH": "$HOME/.heroku/php/bin:$PATH"
			file_put_contents(
				$fn,
				implode("\n", array_map(function($v, $k) { return sprintf('export %s="%s"', $k, $v); }, $profile, array_keys($profile)))
			);
		} else {
			throw new \RuntimeException('Package declares invalid or missing "profile" in "extra"');
		}
	}
}
