<?php

namespace Heroku\Buildpack\PHP;

use Composer\Downloader\ArchiveDownloader;
use Composer\IO\IOInterface;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Cache;
use Composer\Util\ProcessExecutor;
use Composer\Package\PackageInterface;

class Downloader extends ArchiveDownloader
{
	protected $process;

	public function __construct(IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, Cache $cache = null, ProcessExecutor $process = null)
	{
		$this->process = $process ?: new ProcessExecutor($io);

		parent::__construct($io, $config, $eventDispatcher, $cache);
	}

	// extract using cmdline tar, which merges with existing files and folders
	protected function extract($file, $path)
	{
		// we must use cmdline tar, as PharData::extract() messes up symlinks
		$command = 'tar -xzf ' . ProcessExecutor::escape($file) . ' -C ' . ProcessExecutor::escape($path);

		if (0 === $this->process->execute($command, $ignoredOutput)) {
			return;
		}

		throw new \RuntimeException("Failed to execute '$command'\n\n" . $this->process->getErrorOutput());
	}

	// ArchiveDownloader unpacks to a temp dir, then replaces the destination
	// we can't do that, since we need our contents to be merged into the probably existing folder structure
	public function download(PackageInterface $package, $path, $output = true)
	{
		$temporaryDir = $this->config->get('vendor-dir').'/composer/'.substr(md5(uniqid('', true)), 0, 8);
		$this->filesystem->ensureDirectoryExists($temporaryDir);

		// START: from FileDownloader::download()

		if (!$package->getDistUrl()) {
			throw new \InvalidArgumentException('The given package is missing url information');
		}

		$this->io->writeError("  - Installing <info>" . $package->getName() . "</info> (<comment>" . $package->getFullPrettyVersion() . "</comment>)");

		$urls = $package->getDistUrls();
		while ($url = array_shift($urls)) {
			try {
				$fileName = $this->doDownload($package, $temporaryDir, $url);
			} catch (\Exception $e) {
				if ($this->io->isDebug()) {
					$this->io->writeError('');
					$this->io->writeError('Failed: ['.get_class($e).'] '.$e->getCode().': '.$e->getMessage());
				} elseif (count($urls)) {
					$this->io->writeError('');
					$this->io->writeError('    Failed, trying the next URL ('.$e->getCode().': '.$e->getMessage().')');
				}

				if (!count($urls)) {
					throw $e;
				}
			}
		}

		// END: from FileDownloader::download()

		// START: from ArchiveDownloader::download()

		$this->io->writeError('    Extracting archive', true, IOInterface::VERBOSE);

		try {
			$this->extract($fileName, $path);
		} catch (\Exception $e) {
			// remove cache if the file was corrupted
			parent::clearLastCacheWrite($package);
			throw $e;
		}

		$this->filesystem->unlink($fileName);

		if ($this->filesystem->isDirEmpty($this->config->get('vendor-dir').'/composer/')) {
			$this->filesystem->removeDirectory($this->config->get('vendor-dir').'/composer/');
		}
		if ($this->filesystem->isDirEmpty($this->config->get('vendor-dir'))) {
			$this->filesystem->removeDirectory($this->config->get('vendor-dir'));
		}

		$this->io->writeError('');

		// END: from ArchiveDownloader::download()
	}
}
