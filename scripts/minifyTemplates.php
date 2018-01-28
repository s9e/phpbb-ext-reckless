<?php

/**
* @package   s9e\reckless
* @copyright Copyright (c) 2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

if (isset($_SERVER['argv'][1]))
{
	minifyDir($_SERVER['argv'][1]);
}

function minifyDir($path)
{
	array_map(__NAMESPACE__ . '\\minifyFile', glob($path . '/*.html'));
	array_map(__FUNCTION__, glob($path . '/*', GLOB_ONLYDIR));
}

function minifyFile($filepath)
{
	file_put_contents($filepath, minifyTemplate(file_get_contents($filepath)));
}

function minifyTemplate($template)
{
	$template = trim($template);
	$template = preg_replace('(>\\n\\s+<)', ">\n<", $template);
	$template = preg_replace_callback(
		'(<\\w+(?> [-\\w]++="[^"<>]++")++(?: /)?>)',
		function ($m)
		{
			return preg_replace('("([-#\\w]++)")', '$1', $m[0]);
		},
		$template
	);

	return $template;
}