<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Reckless;

use Twig_Source;
use phpbb\template\twig\lexer;

class Flexer extends lexer
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

		if (strpos($filename, 'acp_') === false)
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