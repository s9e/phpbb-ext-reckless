<?php

use PHPUnit\Framework\TestCase;
use s9e\reckless\minifier;

class minifierTest extends TestCase
{
	/**
	* @dataProvider getMinifyTemplateTests
	*/
	public function testMinifyTemplate($original, $expected)
	{
		$this->assertEquals($expected, (new minifier)->minifyTemplate($original));
	}

	public function getMinifyTemplateTests()
	{
		return [
			[
				'',
				''
			],
			[
				'<div>..</div>
				<div>...</div>',
				'<div>..</div><div>...</div>'
			],
			[
				'<ol>
					<li><b>...</b>
					<li><i>...</i>
				</ol>',
				'<ol><li><b>...</b><li><i>...</i></ol>'
			],
			[
				'<a>...</a>
				<a>...</a>',
				'<a>...</a> <a>...</a>'
			],
			[
				'<div id="foo" title="foo bar">..</div>',
				'<div id=foo title="foo bar">..</div>'
			],
		];
	}
}