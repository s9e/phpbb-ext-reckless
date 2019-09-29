<?php

use PHPUnit\Framework\TestCase;
use s9e\reckless\Minifier;

class MinifierTest extends TestCase
{
	/**
	* @dataProvider getMinifyTemplateTests
	*/
	public function testMinifyTemplate($original, $expected)
	{
		$this->assertEquals($expected, (new Minifier)->minifyTemplate($original));
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
			[
				'<input name="foo" required="">
				<input name="bar" required="required">',
				'<input name=foo required> <input name=bar required>'
			],
			[
				'<input name="foo" /><br /><img src="img.png" />',
				'<input name=foo><br><img src=img.png>'
			],
			[
				'<br />

				<!-- NOTE: text -->
				<a href="#">...</a>',
				'<br><a href=#>...</a>'
			],
			[
				'<!-- IF foo --><a href="#">...</a><!-- ENDIF -->',
				'<!-- IF foo --><a href=#>...</a><!-- ENDIF -->',
			],
			[
				'<li>
					<a href="{U_SEARCH_SELF}" role="menuitem">
						<i class="icon fa-file-o fa-fw icon-gray" aria-hidden="true"></i><span>{L_SEARCH_SELF}</span>
					</a>
				</li>',
				'<li><a href="{U_SEARCH_SELF}" role=menuitem> <i class="icon fa-file-o fa-fw icon-gray" aria-hidden=true></i><span>{L_SEARCH_SELF}</span> </a></li>'
			],
			[
				'<a class="footer-link" href="{{ U_PRIVACY }}" title="{{ lang(\'PRIVACY_LINK\') }}" role="menuitem">..</a>',
				'<a class=footer-link href="{{ U_PRIVACY }}" title="{{ lang(\'PRIVACY_LINK\') }}" role=menuitem>..</a>'
			],
		];
	}
}