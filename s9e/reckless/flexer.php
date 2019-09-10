<?php

/**
* @package   s9e\reckless
* @copyright Copyright (c) 2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use Twig_Source;
use phpbb\template\twig\lexer;

class flexer extends lexer
{
	/**
	* @var minifier
	*/
	protected $minifier;

	public function setMinifier(minifier $minifier)
	{
		$this->minifier = $minifier;
	}

	public function tokenize($code, $filename = null)
	{
		if ($code instanceof Twig_Source)
		{
			return $this->tokenize($code->getCode(), $code->getName());
		}

		if (isset($this->minifier))
		{
			$code = $this->minifier->minifyTemplate($code);
		}
$tokens=parent::tokenize($code, $filename);
file_put_contents('/tmp/'.basename($filename).'.txt',print_r($tokens,true));

		return $tokens;
	}
}