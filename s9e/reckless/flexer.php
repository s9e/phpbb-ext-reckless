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

	public function tokenize($code, $filename = null)
	{
		if ($code instanceof Twig_Source)
		{
			$filename = $code->getName();
			$code     = $code->getCode();
		}

		if (substr($filename, 0, 4) !== 'acp_')
		{
			if (!isset($this->minifier))
			{
				$this->minifier = new minifier;
			}
			$code = $this->minifier->minifyTemplate($code);
		}

		return parent::tokenize($code, $filename);
	}
}