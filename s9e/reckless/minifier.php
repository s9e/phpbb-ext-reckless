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
	public function minifyTemplate(string $template): string
	{
		$replacements = [
			// Remove end/spaceless directives
			'(\\{% (?:end)?spaceless %\\})' => '',

			// Remove comments that are not template directives
			'(<!--(?:[<\\n]| NOTE:).*?-->)s' => '',

			// Minify self-closing tags
			'(<(?:br|hr|input|link|meta)[^>]*?\\K\\s*/(?=>))' => ''
		];
		$template = $this->encodeScripts($template);
		$template = preg_replace(array_keys($replacements), $replacements, $template);
		$template = $this->replaceInterElementWhitespace($template);
		$template = $this->minifyCSS($template);
//		$template = $this->minifyJavaScript($template);
		$template = $this->minifyAttributes($template);
		$template = $this->decodeScripts($template);

		return trim($template);
	}

	protected function replaceInterElementWhitespace(string $template): string
	{
		$regexp = '((?:<[^>]++>|\\{%.*?%\\})(*:tag)|\\n\\s++(*:ws)|(?:[^\\n<{]++|.)(*:text))';
		preg_match_all($regexp, $template, $matches);

		$tokens = [['text', '']];
		foreach ($matches[0] as $i => $content)
		{
			$type = $matches['MARK'][$i];
			if ($type === 'tag' && preg_match('(^</?(?:[abius]|em|span|strong)\\b)', $content))
			{
				$type = 'text';
			}

			$tokens[] = [$type, $content];
		}
		$tokens[] = ['text', ''];

		$template = '';
		foreach ($tokens as $i => [$type, $content])
		{
			if ($type === 'ws')
			{
				$content = ($tokens[$i - 1][0] === 'text' && $tokens[$i + 1][0] === 'text') ? ' ' : '';
			}
			$template .= $content;
		}

		return $template;
	}

	protected function decodeScripts(string $template): string
	{
		return $this->replaceScripts($template, 'base64_decode');
	}

	protected function encodeScripts(string $template): string
	{
		return $this->replaceScripts($template, 'base64_encode');
	}

	protected function minifyAttributes(string $template): string
	{
		return preg_replace_callback(
			// Match everything from the start of a tag until we don't understand what's going
			// on anymore, e.g. when inline Twig syntax gets mixed in
			'(<\\w+(?:\\s+[-\\w]+="[^"{}]*")+)',
			function ($m)
			{
				$html = $m[0];

				// Minify whitespace between attributes
				$html = preg_replace('(\\s+([-\\w]+="[^"{}]*"))', ' $1', $html);

				// Remove quotes in attributes and minify empty attributes
				$html = preg_replace('((?<=\\s)([-\\w]++)(?:="(?:\\1)?"|(=)"([^\\s<=>"{}]*)"))', '$1$2$3', $html);

				return $html;
			},
			$template
		);
	}

	protected function minifyCSS(string $template): string
	{
		return preg_replace_callback(
			'( style="\\K[^"]++(?="[^<>]*+>))',
			function ($m)
			{
				if (!class_exists(CSSMinifier::class))
				{
					include __DIR__ . '/include.php';
				}

				$minifier = new CSSMinifier('*{' . $m[0] . '}');
				$css = substr($minifier->minify(), 2, -1);

				// Twig wants a space after double braces
				$css = str_replace('{{', '{{ ', $css);

				return $css;
			},
			$template
		);
	}

	protected function replaceScripts(string $template, callable $callback): string
	{
		return preg_replace_callback(
			'((<script[^>]*>)(.*?)(</script>))is',
			function ($m) use ($callback)
			{
				return $m[1] . $callback($m[2]) . $m[3];
			},
			$template
		);
	}
}