<?php

/**
* @package   s9e\reckless
* @copyright Copyright (c) 2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

class AssetsMinifier
{
	public $brotli    = __DIR__ . '/bin/brotli -f';
	public $gzip      = __DIR__ . '/bin/zopfli -i100';
	public $minifiers = [
		'css' => '',
		'js'  => ''
	];

	public function minifyFile($filepath)
	{
		$brFilepath = $filepath . '.br';
		$gzFilepath = $filepath . '.gz';

		$fileTime = filemtime($filepath);
		$brTime   = file_exists($brFilepath) ? filemtime($brFilepath) : -1;
		$gzTime   = file_exists($gzFilepath) ? filemtime($gzFilepath) : -1;

		if ($brTime === $fileTime && $gzTime === $fileTime)
		{
			return;
		}

		// Minify the file if applicable
		$methodName = 'minify' . ucfirst(substr($filepath, strrpos($filepath, '.'))) . 'File';
		if (method_exists($this, $methodName))
		{
			$this->$methodName($filepath);
		}
		elseif (isset($this->minifiers[$ext]))
		{
			$this->exec($this->minifiers[$ext], $filepath);

			// Reset the file's modification time
			$fileTime = filemtime($filepath);
		}

		// Create compressed variants
		$this->exec($this->brotli, $filepath);
		$this->exec($this->gzip,   $filepath);

		// Sync the compressed variants to the original file's time
		touch($brFilepath, $fileTime);
		touch($gzFilepath, $fileTime);
	}

	protected function exec($cmd, $arg)
	{
		system($cmd . ' ' . escapeshellarg($arg));
	}
}