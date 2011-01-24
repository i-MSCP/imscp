/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */
(function($){$.fn.iMSCPtooltips=function(settings){options={type:'notice',msg:'',opacity:0.85,offsetX:10,offsetY:10,fade:200,move:true,icon:true};var options=$.extend(options,settings);return this.each(function(){var $$=$(this);var title=$$.attr('title')||'';var msg=options.msg||$$.attr('title')||'';if(msg!=''){var tooltip,iframe;var tooltip_bg='<div class="tooltip_bg"></div>';var tooltip_icon=(options.icon)?'<span class="tooltip_notice"></span>':'';var tooltip_txt='<span class="tooltip_txt">'+msg+'</span>';var msieFix=function(tooltipObject,e){if($.browser.msie&&parseFloat($.browser.version)==6){if(options.icon){var icon=tooltipObject.find('span');var icon_uri=icon.css('background-image');icon_uri=icon_uri.split('url("')[1].split('")')[0];icon.css('background-image','none').get(0).runtimeStyle.filter="progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+icon_uri+"',sizingMethod='scale')";}
iframe=$('<iframe/>').attr({src:'javascript:\'<html></html>\';',scrolling:'no'}).css({border:'none',display:'block',position:'absolute',top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px',width:tooltip.width(),height:tooltip.height(),opacity:0,'z-index':'0'}).insertBefore(tooltip);}}
$$.hover(function(e){$$.attr('title','');tooltip=$('<div class="tooltip">'+tooltip_bg+tooltip_icon+tooltip_txt+'</div>').appendTo('body');msieFix(tooltip,e);$('.tooltip_bg').css({width:tooltip.width(),height:tooltip.height(),opacity:options.opacity});tooltip.css({top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px'});if(options.move){$$.mousemove(function(e){tooltip.css({top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px'});if((iframe))
iframe.css({top:(e.pageY+options.offsetY)+'px',left:(e.pageX+options.offsetX)+'px'});});}
tooltip.fadeIn(options.fade);},function(){$$.attr('title',title);$$.unbind('mousemove');tooltip.fadeOut(options.fade,function(){$(this).remove()});if((iframe))
iframe.remove();});}});};})(jQuery);
