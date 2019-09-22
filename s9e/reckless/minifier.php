<?php

/**
* @package   s9e\reckless
* @copyright Copyright (c) 2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use MatthiasMullie\Minify\CSS as CSSMinifier;

class minifier
{
	public function minifyTemplate($template)
	{
		$replacements = [
			// Remove inter-element whitespace
			'(>\\n\\s*)' => '>',
			'(\\n\\s*<)' => '<',

			// Remove end/spaceless directives
			'(\\{% (?:end)?spaceless %\\})' => '',

			// Remove comments that are not template directives
			'(<!--(?:[<\\n]| NOTE:).*?-->)s' => '',

			// Replace self-closing tags
			'(<(?:br|hr|input|link|meta)[^>]*?\\K\\s*/(?=>))' => '',
		];
		$template = preg_replace(array_keys($replacements), $replacements, $template);
		$template = $this->minifyCSS($template);
//		$template = $this->minifyJavaScript($template);
		$template = $this->minifyAttributes($template);

		return trim($template);
	}

	/**
	* 
	*
	* @return void
	*/
	protected function minifyAttributes($template)
	{
		return preg_replace_callback(
			// Match everything from the start of a tag until we don't understand what's going
			// on anymore, e.g. with inline Twig syntax mixed in
			'(<\\w+(?:\\s+[-\\w]+="[^"]*")+)',
			function ($m)
			{
				// Remove quotes in attributes and convert empty attributes
				return preg_replace('(=""|(=)"([^\\s<=>"{}]*)")', '$1$2', $m[0]);
			},
			$template
		);
	}

	/**
	* 
	*
	* @return void
	*/
	protected function minifyCSS($template)
	{
		return preg_replace_callback(
			'( style="\\K[^"]++(?="[^<>]*+>))',
			function ($m)
			{
				$minifier = new CSSMinifier('*{' . $m[0] . '}');

				return substr($minifier->minify(), 2, -1);
			},
			$template
		);
	}
}