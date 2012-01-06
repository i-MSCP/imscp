/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Layout
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.1
 */
(function ($) {

	var tooltipElement = {},
		// the current tooltipped element
		current,
		// the text of the current element, used for restoring
		text,
		// timeout id for delayed tooltips
		tooltipId,
		// flag for mouse tracking
		followCursor = true, // TODO Something goes wrong here
		// IE 5.5 or 6
		IE = $.browser.msie && /MSIE\s(5\.5|6\.)/.test(navigator.userAgent);

	$.imscpTooltip = {
		defaultsSettings:{ // Default settings
			msg:'',
			opacity:0.85,
			top:10,
			left:10,
			delay:500,
			fade:true,
			extraClass:"",
			id:"imscpTooltip"
		}
	};

	$.fn.extend({
		imscpTooltip:function (settings) { // Main
			settings = $.extend({}, $.imscpTooltip.defaultsSettings, settings);
			createTooltipElement(settings);

			return this.each(
				function () {
					// Store current tooltip settings
					$.data(this, "imscpTooltip", settings);

					this.tOpacity = tooltipElement.parent.css("opacity");

					// Set tooltip text
					this.tooltipText = settings.msg || this.title || '';
					$(this).removeAttr("title");

					// Remove alt attribute to prevent default tooltip in IE
					this.alt = "";
				}).mouseover(save).mouseout(hide).click(hide);
		}
	});

	// Return settings for given element
	function settings(element) {
		return $.data(element, "imscpTooltip");
	}

	// Create tooltip element and add it to the document
	function createTooltipElement(settings) {
		if (tooltipElement.parent) {
			return;
		}

		tooltipElement.parent = $('<div id="' + settings.id + '"><div class="tooltipBody"></div></div>').appendTo(document.body).hide();

		if ($.fn.bgiframe) {
			tooltipElement.parent.bgiframe();
		}

		// save references to body element
		tooltipElement.body = $('div.tooltipBody', tooltipElement.parent);
	}

	// main event handler to start showing tooltips
	function handle(event) {
		// show helper, either with timeout or on instant
		if (settings(this).delay) {
			tooltipId = setTimeout(show, settings(this).delay);
		} else {
			show();
		}

		// if selected, update the helper position when the mouse moves
		followCursor = !settings(this).followCursor;
		$(document.body).bind('mousemove', update);

		// update at least once
		update(event);
	}

	// save elements title before the tooltip is displayed
	function save() {
		if (this == current || !this.tooltipText) {
			return;
		}

		// save current tooltip
		current = this;

		// Get tooltip text
		text = this.tooltipText;

		tooltipElement.body.html(text).show();

		// add an optional class for this tip
		tooltipElement.parent.addClass(settings(this).extraClass);

		handle.apply(this, arguments);
	}

	// delete timeout and show helper
	function show() {
		tooltipId = null;

		if ((!IE || !$.fn.bgiframe) && settings(current).fade) {
			if (tooltipElement.parent.is(":animated")) {
				tooltipElement.parent.stop().show().fadeTo(settings(current).fade, current.tOpacity);
			} else {
				tooltipElement.parent.is(':visible') ? tooltipElement.parent.fadeTo(settings(current).fade, current.tOpacity) : tooltipElement.parent.fadeIn(settings(current).fade);
			}
		} else {
			tooltipElement.parent.show();
		}

		update();
	}

	/**
	 * callback for mousemove
	 * updates the helper position
	 * removes itself when no current element
	 */
	function update(event) {
		if (event && event.target.tagName == "OPTION") {
			return;
		}

		// stop updating when tracking is disabled and the tooltip is visible
		if (!followCursor && tooltipElement.parent.is(":visible")) {
			$(document.body).unbind('mousemove', update)
		}

		// if no current element is available, remove this listener
		if (current == null) {
			$(document.body).unbind('mousemove', update);
			return;
		}

		// remove position helper classes
		tooltipElement.parent.removeClass("viewport-right").removeClass("viewport-bottom");

		var left = tooltipElement.parent[0].offsetLeft;
		var top = tooltipElement.parent[0].offsetTop;

		if (event) {
			// position the helper 15 pixel to bottom right, starting from mouse position
			left = event.pageX + settings(current).left;
			top = event.pageY + settings(current).top;
			var right = 'auto';

			if (settings(current).positionLeft) {
				right = $(window).width() - left;
				left = 'auto';
			}
			tooltipElement.parent.css({left:left, right:right, top:top});
		}

		var v = viewport(),
			h = tooltipElement.parent[0];

		// check horizontal position
		if (v.x + v.cx < h.offsetLeft + h.offsetWidth) {
			left -= h.offsetWidth + 20 + settings(current).left;
			tooltipElement.parent.css({left:left + 'px'}).addClass("viewport-right");
		}

		// check vertical position
		if (v.y + v.cy < h.offsetTop + h.offsetHeight) {
			top -= h.offsetHeight + 20 + settings(current).top;
			tooltipElement.parent.css({top:top + 'px'}).addClass("viewport-bottom");
		}
	}

	function viewport() {
		return {
			x:$(window).scrollLeft(),
			y:$(window).scrollTop(),
			cx:$(window).width(),
			cy:$(window).height()
		};
	}

	// hide helper and restore added classes and the title
	function hide(event) {

		// clear timeout if possible
		if (tooltipId) {
			clearTimeout(tooltipId);
		}

		// no more current element
		current = null;

		var tsettings = settings(this);

		function complete() {
			tooltipElement.parent.removeClass(tsettings.extraClass).hide().css("opacity", "");
		}

		if ((!IE || !$.fn.bgiframe) && tsettings.fade) {
			if (tooltipElement.parent.is(':animated')) {
				tooltipElement.parent.stop().fadeTo(tsettings.fade, 0, complete);
			} else {
				tooltipElement.parent.stop().fadeOut(tsettings.fade, complete);
			}
		} else {
			complete();
		}
	}
})(jQuery);
