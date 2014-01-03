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
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.4
 */
(function ($) {

	var tooltipElement = {},
		// the current tooltipped element
		currentTooltip,
		// the text of the current element, used for restoring
		tooltipText,
		// timeout id for delayed tooltips
		tooltipId,
		// flag for mouse tracking
		followCursor = true; // TODO Something goes wrong here

	$.imscpTooltip = {
		defaultsSettings:{ // Default getSettings
			msg:"",
			opacity:0.85,
			top:10,
			left:10,
			delay:200,
			fade:true,
			extraClass:"",
			id:"imscpTooltip"
		}
	};

	$.fn.extend({
		imscpTooltip:function (parameters) { // Main
			settings = $.extend({}, $.imscpTooltip.defaultsSettings, parameters);
			createTooltipElement(settings);

			return this.each(
				function () {
					if(settings.msg || this.title) {
						// Store settings for current tooltip
						$.data(this, "imscpTooltip", settings);

						// get tooltip opacity
						this.tOpacity = tooltipElement.parent.css("opacity");

						// set tooltip text
						this.tooltipText = settings.msg || this.title;
						$(this).removeAttr("title");

						// Remove alt attribute to prevent default tooltip in IE
						this.alt = "";

						$(this).mouseover(save).mouseout(hide).click(hide);
					}
				}
			);
		}
	});

	// Return getSettings for given element
	function getSettings(element)
	{
		return $.data(element, "imscpTooltip");
	}

	// Create tooltip element and add it to the document
	function createTooltipElement(settings)
	{
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
	function handle(event)
	{
		// show tooltip, either with timeout or on instant
		if (getSettings(this).delay) {
			tooltipId = setTimeout(show, getSettings(this).delay);
		} else {
			show();
		}

		// if selected, update the helper position when the mouse moves
		followCursor = !getSettings(this).followCursor;
		$(document.body).bind('mousemove', update);

		// update at least once
		update(event);
	}

	// save elements title before the tooltip is displayed
	function save()
	{
		if (this == currentTooltip || !this.tooltipText) {
			return;
		}

		// save current tooltip
		currentTooltip = this;

		// get tooltip text
		tooltipText = this.tooltipText;

		tooltipElement.body.html(tooltipText).show();

		// add an optional class for this tooltip
		//alert(getSettings(this).extraClass)
		tooltipElement.parent.addClass(getSettings(this).extraClass);

		handle.apply(this, arguments);
	}

	// delete timeout and show helper
	function show()
	{
		tooltipId = null;

		if (getSettings(currentTooltip).fade) {
			if (tooltipElement.parent.is(":animated")) {
				tooltipElement.parent.stop().show().fadeTo(getSettings(currentTooltip).fade, currentTooltip.tOpacity);
			} else {
				tooltipElement.parent.is(':visible') ? tooltipElement.parent.fadeTo(getSettings(currentTooltip).fade, currentTooltip.tOpacity) : tooltipElement.parent.fadeIn(getSettings(currentTooltip).fade);
			}
		} else {
			tooltipElement.parent.show();
		}

		update();
	}

	/**
	 * callback for mousemove
	 * updates the tooltip position
	 * removes itself when no current element
	 */
	function update(event)
	{
		if (event && event.target.tagName == "OPTION") {
			return;
		}

		// stop updating when tracking is disabled and the tooltip is visible
		if (!followCursor && tooltipElement.parent.is(":visible")) {
			$(document.body).unbind('mousemove', update)
		}

		// if no current element is available, remove this listener
		if (currentTooltip == null) {
			$(document.body).unbind('mousemove', update);
			return;
		}

		// remove position helper classes
		tooltipElement.parent.removeClass("viewport-right").removeClass("viewport-bottom");

		var left = tooltipElement.parent[0].offsetLeft;
		var top = tooltipElement.parent[0].offsetTop;

		if (event) {
			// position the helper 15 pixel to bottom right, starting from mouse position
			left = event.pageX + getSettings(currentTooltip).left;
			top = event.pageY + getSettings(currentTooltip).top;
			var right = 'auto';

			if (getSettings(currentTooltip).positionLeft) {
				right = $(window).width() - left;
				left = 'auto';
			}
			tooltipElement.parent.css({left:left, right:right, top:top});
		}

		var v = viewport(),
			h = tooltipElement.parent[0];

		// check horizontal position
		if (v.x + v.cx < h.offsetLeft + h.offsetWidth) {
			left -= h.offsetWidth + 20 + getSettings(currentTooltip).left;
			tooltipElement.parent.css({left:left + 'px'}).addClass("viewport-right");
		}

		// check vertical position
		if (v.y + v.cy < h.offsetTop + h.offsetHeight) {
			top -= h.offsetHeight + 20 + getSettings(currentTooltip).top;
			tooltipElement.parent.css({top:top + 'px'}).addClass("viewport-bottom");
		}
	}

	function viewport()
	{
		return {
			x:$(window).scrollLeft(),
			y:$(window).scrollTop(),
			cx:$(window).width(),
			cy:$(window).height()
		};
	}

	// hide helper and restore added classes and the title
	function hide(event)
	{
		// clear timeout if possible
		if (tooltipId) {
			clearTimeout(tooltipId);
		}

		// no more current element
		currentTooltip = null;

		var tsettings = getSettings(this);

		function complete() {
			tooltipElement.parent.removeClass(tsettings.extraClass).hide().css("opacity", "");
		}

		if (tsettings.fade) {
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
