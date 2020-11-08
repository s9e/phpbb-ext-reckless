<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use Twig\Source;
use phpbb\template\twig\lexer;

class Flexer extends lexer
{
	/**
	* @var Minifier
	*/
	protected $minifier;

	public function tokenize(Source $source)
	{
		$filename = $source->getName();
		if (strpos($filename, 'acp_') === false)
		{
			$code   = $this->getMinifier()->minifyTemplate($source->getCode());
			$source = new Source($code, $filename);
		}

		return parent::tokenize($source);
	}

	protected function getMinifier(): Minifier
	{
		if (!isset($this->minifier))
		{
			$this->minifier = new Minifier;
		}

		return $this->minifier;
	}
}