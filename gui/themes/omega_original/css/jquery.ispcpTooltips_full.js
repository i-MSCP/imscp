/**
 * ispCP ω (OMEGA) a Virtual Hosting Control Panel
 * Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
 *
 * Version:	$Id$
 * @link	http://isp-control.net
 * @author	Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * The ispCP ω Home Page is:
 *
 *    http://isp-control.net
 */

/**
 * IspCP Jquery Tooltips plugin
 *
 * This plugin provides a function to create nicetitles/tooltips
 * for one or more html elements.
 *
 * Usage:
 *
 * First, You must ensure that the DOM is fully loaded.
 * For that, you can use the Jquery ready function :
 *
 * $(document).ready(function(){
 *	// plugin call here
 * });
 *
 * 1. Create nicetitle for all links that have 'title' attribut
 * 	$('a').ispCPtooltips();
 *
 * 2. Create nicetitle for one link that has title attribut
 * 	$('#id_link').ispCPtooltips();
 *
 * 3. Tooltip for user helping with custom message
 * 	$('#id_img').ispCPtooltips({msg:'Welcome in our world'});
 *
 * 4. Tooltip for user helping on password input fields with custom message
 *	$(':password').ispCPtooltips({msg:'Please, enter your password here'});
 *
 * See Parameters section in the plugin body for more information about possible options
 * See the Jquery documentation for more information about available selectors
 *
 * Tested on: MSIE 6,7,8, FF and others Gecko's based browser, chrome.
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @version 1.0.0
 * @require Jquery 1.4.1
 */
(function($){

	$.fn.ispCPtooltips = function(settings) {

		/**
		 * Parameters
		 */
		options = {
			// type string Tooltip icon type
			type:		'notice',
			// Tooltip string Message for override the text of title attribut
			// or for elements that have not title attribut
			msg:		'',
			// Opacity int Set to 1 to disable it
			opacity:	0.85,
			// offsetX int Integer that specifies the x-coordinate, in pixels for the tooltip
			// compared to the x-coordinate of the mouse pointer's position.
			offsetX:	10,
			// offsetY int Integer that specifies the y-coordinate, in pixels.
			// compared to the y-coordinate of the mouse pointer's position.
			offsetY:	10,
			// fade int||string Time in milliseconds to the tooltip
			// fade(IN|OUT) effect - Set to 0 for disable it
			// Can be also use string annotation (slow, fast)
			fade:		200,
			// move boolean If set to TRUE, the tooltip follow the cursor
			move:		true,
			// icon boolean If set to TRUE, add a icon in the tooltip
			icon:		true
		};

		var options = $.extend(options, settings);

		// For each selected objects
		return this.each(function() {
			var $$ = $(this);
			var title = $$.attr('title') || '';
			var msg = options.msg || $$.attr('title') || '';

			// Create a tooltip for the current object if it contains a
			// not empty 'title' attribute or if the msg parameter was been defined
			if(msg != '') {
				var tooltip,iframe;
				var tooltip_bg = '<div class="tooltip_bg"></div>';
				var tooltip_icon = (options.icon) ? '<span class="tooltip_notice"></span>' : '';
				var tooltip_txt = '<span class="tooltip_txt">'+msg+'</span>';

				/**
				 *  MSIE 6 Fixes
				 */
				var msieFix = function(tooltipObject, e) {
					if($.browser.msie && parseFloat($.browser.version) == 6) {

						// Fix for icon transparency
						if(options.icon) {
							var icon = tooltipObject.find('span');
							var icon_uri = icon.css('background-image');
							icon_uri = icon_uri.split('url("')[1].split('")')[0];
							icon.css('background-image','none').get(0).runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+icon_uri+"',sizingMethod='scale')";
						}

						// Fix for the stack order of the 'select' elements and tooltips
						iframe = $('<iframe/>') .attr({src:'javascript:\'<html></html>\';',scrolling:'no'}) .
							css({border:'none',display:'block',position:'absolute',top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px',width:tooltip.width(),height:tooltip.height(),opacity:0,'z-index': '0'}).insertBefore(tooltip);
					}
				}

				$$.hover(
					// Handler for the 'onmouseover' event linked to the current object
					function(e){
					// Avoid default browser behavior on title attribute
					$$.attr('title', '');
					// Create the tooltip object and adds it in the DOM
					tooltip = $('<div class="tooltip">'+tooltip_bg+tooltip_icon+tooltip_txt+'</div>').
						appendTo('body');

					// Fix (MSIE6 issue)
					msieFix(tooltip, e);

				 	// Sets the height, width and opacity for the tooltip background
					$('.tooltip_bg').css({width:tooltip.width(),height:tooltip.height(),opacity:options.opacity});
					// Sets the tooltip position
					tooltip.css({top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px'});

					// If the move option is set to TRUE, the tooltip must follow the pointer
					if(options.move) {
						// Handler for the 'onmousemove' event linked to the current object
						$$.mousemove(function(e){
							tooltip.css({top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px'});
							if((iframe))
								iframe.css({top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px'});
						});
					}
					// Show the tooltip with fade effect if set to a value != 0
					tooltip.fadeIn(options.fade);
					},
					// Handler for the 'onmouseout' event linked to the current object
					function(){
						// Restore the default title
						$$.attr('title', title);
						// Remove the mousemove event handler
						$$.unbind('mousemove');
						// Hide the tooltip with fade effect if set to a value != 0 and remove it
						tooltip.fadeOut(options.fade, function(){$(this).remove()});
						if ((iframe))
							iframe.remove();
					}
				); // end hover();
			}
		});  // End foreach
	};  // End ispCPtooltips
})(jQuery); // ispCP plugin tooltips
