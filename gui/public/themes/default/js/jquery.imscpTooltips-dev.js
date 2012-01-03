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
 * @copyright   2009-2011 by Laurent declercq
 * @author      Laurent Declercq <l.declercq@i-mscp.net>
 * @contributor	iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * iMSCP Jquery Tooltips plugin
 *
 * This plugin provides a function to create nicetitles/tooltips for one or more html
 * elements.
 *
 * Usage:
 *
 * First, You must ensure that the DOM is fully loaded. For that, you can use the
 * jQuery ready function like this :
 *
 * $(document).ready(function(){
 *	// plugin call here
 * });
 *
 * 1. Create nicetitle for all links that have a 'title' attribut:
 * 	$('a').iMSCPtooltips();
 *
 * 2. Create nicetitle for one link that has title attribut:
 * 	$('#id_link').iMSCPtooltips();
 *
 * 3. Tooltip for user helping with custom message:
 * 	$('#id_img').iMSCPtooltips({msg:'Welcome in our world'});
 *
 * 4. Tooltip for user helping on password input fields with custom message
 *	$(':password').iMSCPtooltips({msg:'Please, enter your password here'});
 *
 * See the parameters section in the plugin body for more information about possible
 * options. See the Jquery documentation for more information about available selectors
 *
 * Tested on: MSIE 6,7,8, FF and others Gecko's based browser, chrome.
 *
 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version 1.0.0
 * @require Jquery >= 1.4.1
 */
(function($) {
	$.fn.iMSCPtooltips = function(settings) {
		options = {
			// tooltip type (for now, only notice is implemented)
			type:'notice',
			// Tooltip string Message for override the text of title attribut
			// or for elements that have not title attribut
			msg:'',
			// Opacity int Set to 1 to disable it
			opacity:0.85,
			// offsetX int Integer that specifies the x-coordinate, in pixels for the tooltip
			// compared to the x-coordinate of the mouse pointer's position.
			offsetX:10,
			// offsetY int Integer that specifies the y-coordinate, in pixels.
			// compared to the y-coordinate of the mouse pointer's position.
			offsetY:10,
			// fade int||string Time in milliseconds to the tooltip
			// fade(IN|OUT) effect - Set to 0 for disable it
			// Can be also use string annotation (slow, fast)
			fade:200,
			// move boolean If set to TRUE, the tooltip follow the cursor
			move:true,
			// icon boolean If set to TRUE, add a icon in the tooltip
			icon:true
		};

		var options = $.extend(options, settings);

		// For each selected objects
		return this.each(function() {
			var $$ = $(this);
			var title = $$.attr('title') || '';
			var msg = options.msg || $$.attr('title') || '';

			// Create a tooltip for the current object if it contains a
			// not empty 'title' attribute or if the msg parameter was been defined
			if (msg != '') {
				var tooltip,iframe;
				var tooltip_bg = '<div class="tooltip_bg"></div>';
				var tooltip_icon = (options.icon) ? '<span class="tooltip_notice"></span>' : '';
				var tooltip_txt = '<span class="tooltip_txt">' + msg + '</span>';
				var msieFix = function(tooltipObject, e) {

					/**
					 *  MSIE 6 Fixes
					 */
					if ($.browser.msie && parseFloat($.browser.version) == 6) {
						// Fix for icon transparency
						if (options.icon) {
							var icon = tooltipObject.find('span');
							var icon_uri = icon.css('background-image');
							icon_uri = icon_uri.split('url("')[1].split('")')[0];
							icon.css('background-image', 'none').get(0).runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + icon_uri + "',sizingMethod='scale')";
						}
						// Fix for the stack order of the 'select' elements and tooltips
						iframe = $('<iframe/>').attr({src:'javascript:\'<html></html>\';',scrolling:'no'}).css({border:'none',display:'block',position:'absolute',top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px',width:tooltip.width(),height:tooltip.height(),opacity:0,'z-index':'0'}).insertBefore(tooltip);
					}
				}

				$$.hover(
					// Handler for the 'onmouseover' event linked to the current object
					function(e) {
						// Avoid default browser behavior on title attribute
						$$.attr('title', '');

						// Create the tooltip object and adds it in the DOM
						tooltip = $('<div class="tooltip">' + tooltip_bg + tooltip_icon + tooltip_txt + '</div>').appendTo('body');

						// Fix (MSIE6 issue)
						msieFix(tooltip, e);

						// Sets the height, width and opacity for the tooltip background
						$('.tooltip_bg').css({width:tooltip.width(),height:tooltip.height(),opacity:options.opacity});

						// Sets the tooltip position
						tooltip.css({top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px'});

						// If the move option is set to TRUE, the tooltip must follow the pointer
						if (options.move) {
							// Handler for the 'onmousemove' event linked to the current object
							$$.mousemove(function(e) {
								tooltip.css({top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px'});
								if ((iframe)) {
									iframe.css({top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px'});
								}
							});
						}

						// Show the tooltip with fade effect if set to a value != 0
						tooltip.fadeIn(options.fade);
					},
					// Handler for the 'onmouseout' event linked to the current object
					function() {
						// Restore the default title
						$$.attr('title', title);

						// Remove the mousemove event handler
						$$.unbind('mousemove');

						// Hide the tooltip with fade effect if set to a value != 0 and remove it
						tooltip.fadeOut(options.fade, function() {
							$(this).remove()
						});

						if ((iframe)) {
							iframe.remove();
						}
					}
				); // end hover();
			}
		}); // End foreach
	}; // End iMSCPtooltips
})(jQuery); // iMSCP plugin tooltips

// Modified version of the iMSCPtooltips for the software installer
// TODO update the iMSCPtooltips plugin to be able to remove this one
(function($) {
	$.fn.sw_iMSCPtooltips = function(settings) {
		options = {
			type:'notice',
			msg:'',
			opacity:0.85,
			offsetX:10,
			offsetY:10,
			fade:200,
			move:true,
			icon:true
		};

		var options = $.extend(options, settings);

		return this.each(function() {
			var $$ = $(this);
			var title = $$.attr('title') || '';
			var msg = options.msg || $$.attr('title') || '';

			if (msg != '') {
				var tooltip,iframe;
				var tooltip_bg = '<div class="sw_tooltip_bg"></div>';
				var tooltip_icon = (options.icon) ? '<span class="sw_tooltip_notice"></span>' : '';
				var tooltip_txt = '<span class="sw_tooltip_txt">' + msg + '</span>';
				var msieFix = function(tooltipObject, e) {
					if ($.browser.msie && parseFloat($.browser.version) == 6) {
						if (options.icon) {
							var icon = tooltipObject.find('span');
							var icon_uri = icon.css('background-image');
							icon_uri = icon_uri.split('url("')[1].split('")')[0];
							icon.css('background-image', 'none').get(0).runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + icon_uri + "',sizingMethod='scale')";
						}

						iframe = $('<iframe/>').attr({src:'javascript:\'<html></html>\';',scrolling:'no'}).css({border:'none',display:'block',position:'absolute',top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px',width:tooltip.width(),height:tooltip.height(),opacity:0,'z-index':'0'}).insertBefore(tooltip);
					}
				}

				$$.hover(function(e) {
					$$.attr('title', '');
					tooltip = $('<div class="sw_tooltip">' + tooltip_bg + tooltip_icon + tooltip_txt + '</div>').appendTo('body');
					msieFix(tooltip, e);
					$('.sw_tooltip_bg').css({width:tooltip.width(),height:tooltip.height(),opacity:options.opacity});
					tooltip.css({top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px'});

					if (options.move) {
						$$.mousemove(function(e) {
							tooltip.css({top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px'});
							if ((iframe))
								iframe.css({top:(e.pageY + options.offsetY) + 'px',left:(e.pageX + options.offsetX) + 'px'});
						});
					}

					tooltip.fadeIn(options.fade);
				}, function() {
					$$.attr('title', title);
					$$.unbind('mousemove');

					tooltip.fadeOut(options.fade, function() {
						$(this).remove()
					});

					if ((iframe)) {
						iframe.remove();
					}
				});
			}
		});
	};
})(jQuery);
