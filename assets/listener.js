(function (window)
{
	function isNormalLeftClick(e)
	{
		return e.buttons <= 1 && !e.altKey && !e.ctrlKey && !e.metaKey && !e.shiftKey;
	}

	/** @var {?Node} */
	let preventTarget;
	window.addEventListener('click', function (e)
	{
		if (isNormalLeftClick(e) && preventTarget && e.target.isSameNode(preventTarget))
		{
			e.preventDefault();
		}
		preventTarget = false;
	});
	window.addEventListener('mousedown', function (e)
	{
		preventTarget = false;
		if (!isNormalLeftClick(e))
		{
			return;
		}
		var target = e.target, anchor = target;
		while (anchor)
		{
			if (anchor.tagName === 'A')
			{
				if (anchor.host === window.location.host && anchor.getAttribute('href') !== '#' && anchor.className.indexOf('dropdown') < 0)
				{
					target.click();
					preventTarget = target;
				}
				return;
			}
			anchor = anchor.parentNode;
		}
	});
})(window);