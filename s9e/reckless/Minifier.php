<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use MatthiasMullie\Minify\CSS as CSSMinifier;

class Minifier
{
	public function minifyTemplate(string $template): string
	{
		$replacements = [
			// Remove end/spaceless directives
			'(\\{% (?:end)?spaceless %\\})' => '',

			// Remove comments that are not template directives
			'(<!--(?:[<\\n]| NOTE:).*?-->)s' => '',

			// Minify self-closing tags
			'(<(?:br|hr|img|input|link|meta)[^>]*?\\K\\s*/(?=>))' => ''
		];
		$template = $this->encodeScripts($template);
		$template = preg_replace(array_keys($replacements), $replacements, $template);
		$template = $this->replaceInterElementWhitespace($template);
		$template = $this->removeOptionalTags($template);
		$template = $this->minifyCSS($template);
//		$template = $this->minifyJavaScript($template);
		$template = $this->minifyAttributes($template);
		$template = $this->decodeScripts($template);

		return trim($template);
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
		$attrRegexp = '[-\\w]+(?:="(?:[^"{}]|\\{+\\s*(?:lang\\(\'\\w++\'\\)|\\w++)\\s*\\}+)*")?';

		return preg_replace_callback(
			// Match everything from the start of a tag until we don't understand what's going
			// on anymore, e.g. when inline Twig syntax gets mixed in
			'(<\\w+(?:\\s+' . $attrRegexp . ')+)',
			function ($m) use ($attrRegexp)
			{
				$html = $m[0];

				// Minify whitespace between attributes
				$html = preg_replace('(\\s+(' . $attrRegexp . '))', ' $1', $html);

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

				$minifier = new CSSMinifier('b{' . $m[0] . '}');
				$css = substr($minifier->minify(), 2, -1);

				// Twig wants a space after double braces
				$css = str_replace('{{', '{{ ', $css);

				return $css;
			},
			$template
		);
	}

	protected function minifyJS(string $template): string
	{
		// Ensure the JS starts with a semicolon and does not look like a file path
		(new MatthiasMullie\Minify\JS(';' . $js))->minify();
	}

	protected function removeOptionalTags(string $template): string
	{
		// https://html.spec.whatwg.org/multipage/syntax.html#syntax-tag-omission
		$regexps = [
			'</d[dt]>(?=<(?:d[dt]|/dl)>)',
			'</li>(?=<(?:li[^>]*|/[ou]l)>)',
			'</option>(?=<(?:option|/optgroup|/select)>)',
			'</p>(?=<p>)'
		];
		$template = preg_replace('(' . implode('|', $regexps) . ')', '', $template);

		return $template;
	}

	protected function replaceInterElementWhitespace(string $template): string
	{
		// Match most inline HTML elements
		$inlineRegexp = '(^</?(?:[qu]|a(?:bbr|udio)?|b(?:d[io]|utton)?|c(?:it|od)e|d(?:ata(?:list)?|el|fn)|em(?:bed)?|i(?:frame|mg|n(?:put|s))?|kbd|label|m(?:ark|eter)|o(?:bjec|utpu)t|p(?:icture|rogress)|ruby|s(?:amp|cript|elect|mall|pan|trong|u[bp])?|t(?:extarea|ime)|v(?:ar|ideo)|wbr)\\b)';

		// Match tags and Twig blocks, spans of whitespace, and anything else
		$regexp = '((?:<[^>]++>|\\{%.*?%\\})(*:tag)|\\n\\s*+(*:ws)|(?:[^\\n<{]++|.)(*:text))s';
		preg_match_all($regexp, $template, $matches);

		$lastType = 'text';
		$tokens   = [['text', '']];
		foreach ($matches[0] as $i => $content)
		{
			$type = $matches['MARK'][$i];
			if ($type === 'tag')
			{
				if (preg_match($inlineRegexp, $content))
				{
					$type = 'text';
				}
				elseif (preg_match('(^<!|^\\{%)', $content))
				{
					$type = $lastType;
				}
			}

			if ($type === 'tag' || $type === 'text')
			{
				$lastType = $type;
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

		// Remove redundant spaces around nested blocks
		$template = preg_replace('((?<= )(<!-- IF [^-]++-->|\\{% if [^%]++%\\})\\K )', '', $template);
		$template = preg_replace('( (<!-- ENDIF -->|\\{% endif %\\})(?= ))', '$1', $template);

		return $template;
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