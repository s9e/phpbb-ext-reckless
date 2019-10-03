(function (window)
{
	function isInternalLink(a)
	{
		return a.host === window.location.host && a.getAttribute('href') !== '#' && !/dropdown/.test(a.className);
	}

	function isNormalLeftClick(e)
	{
		return e.buttons === 1 && !e.altKey && !e.ctrlKey && !e.metaKey && !e.shiftKey;
	}

	/** @type {?EventTarget} */
	let preventTarget = null;
	window.addEventListener('click', function (e)
	{
		if (isNormalLeftClick(e) && preventTarget && e.target.isSameNode(preventTarget))
		{
			e.preventDefault();
		}
		preventTarget = null;
	});
	window.addEventListener('mousedown', function (e)
	{
		preventTarget = null;
		if (!isNormalLeftClick(e))
		{
			return;
		}

		let target = e.target, anchor = target;
		while (anchor)
		{
			if (anchor.tagName === 'A')
			{
				if (isInternalLink(anchor))
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