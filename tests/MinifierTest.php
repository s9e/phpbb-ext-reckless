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
					<li>..</li>
					<li>..</li>
				</ol>',
				'<ol><li>..<li>..</ol>'
			],
			[
				'<dl>
					<dt>..</dt>
					<dd>..</dd>
				</dl>',
				'<dl><dt>..<dd>..</dl>'
			],
			[
				'<ol>
					<li><b>...</b>
					<li><i>...</i>
				</ol>',
				'<ol><li><b>...</b><li><i>...</i></ol>'
			],
			[
				'<div>
					<b>...</b>
					<i>...</i>
				</div>',
				'<div><b>...</b> <i>...</i></div>'
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
				'<div>..</div>
				<!-- IF foo -->
					<div>..</div>
				<!-- ENDIF -->
				<div>..</div>',
				'<div>..</div><!-- IF foo --><div>..</div><!-- ENDIF --><div>..</div>',
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
			[
				'<li class="breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">',
				'<li class=breadcrumbs itemscope itemtype=http://schema.org/BreadcrumbList>'
			],
			[
				'<div style="border: solid 1px #000;">..</div>',
				'<div style="border:solid 1px #000">..</div>'
			],
			[
				'<span style="color: #123456;">..</span>',
				'<span style=color:#123456>..</span>'
			],
			[
				'<span>
					<!-- IF foo -->
						<!-- IF bar -->
							<b>..</b>
						<!-- ENDIF -->
					<!-- ENDIF -->
				</span>',
				'<span> <!-- IF foo --><!-- IF bar --><b>..</b><!-- ENDIF --><!-- ENDIF --> </span>'
			],
			[
				"<hr>\n<hr>",
				'<hr><hr>'
			],
			[
				'<div class="post <!-- IF postrow.S_ROW_COUNT is odd -->bg1<!-- ELSE -->bg2<!-- ENDIF --><!-- IF postrow.S_UNREAD_POST --> unreadpost<!-- ENDIF --><!-- IF postrow.S_POST_REPORTED --> reported<!-- ENDIF -->">',
				'<div class="post <!-- IF postrow.S_ROW_COUNT is odd -->bg1<!-- ELSE -->bg2<!-- ENDIF --><!-- IF postrow.S_UNREAD_POST --> unreadpost<!-- ENDIF --><!-- IF postrow.S_POST_REPORTED --> reported<!-- ENDIF -->">'
			],
			[
				'<a href="<!-- IF postrow.contact.U_CONTACT -->{postrow.contact.U_CONTACT}<!-- ELSE -->{postrow.U_POST_AUTHOR}<!-- ENDIF -->" title="{postrow.contact.NAME}"<!-- IF $S_LAST_CELL --> class="last-cell"<!-- ENDIF --><!-- IF postrow.contact.ID eq \'jabber\' --> onclick="popup(this.href, 750, 320); return false;"<!-- ENDIF -->>',
				'<a href="<!-- IF postrow.contact.U_CONTACT -->{postrow.contact.U_CONTACT}<!-- ELSE -->{postrow.U_POST_AUTHOR}<!-- ENDIF -->" title="{postrow.contact.NAME}"<!-- IF $S_LAST_CELL --> class="last-cell"<!-- ENDIF --><!-- IF postrow.contact.ID eq \'jabber\' --> onclick="popup(this.href, 750, 320); return false;"<!-- ENDIF -->>'
			],
			[
				'<p>
					Text starts here
					<b>ends here</b>
				</p>',
				'<p>Text starts here <b>ends here</b></p>'
			],
		];
	}
}