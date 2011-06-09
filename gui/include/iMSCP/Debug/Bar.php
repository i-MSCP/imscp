<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP Team.
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
 * @package     iMSCP
 * @package     iMSCP_Debug
 * @subpackage  Bar_Plugin
 * @copyright   2010-2011 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * i-MSCP Debug Bar component.
 *
 * @package     iMSCP
 * @package     iMSCP_Debug
 * @subpackage  Bar
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Debug_Bar
{
    /**
     * Events manager instance.
     *
     * @var iMSCP_Events_Manager
     */
    protected $_enventsManager;

    /**
     * @var iMSCP_Events_Response
     */
    protected $_event;

    /**
     * Contains registered plugins for debug bar
     *
     * @var iMSCP_Debug_Bar_Plugin_Interface
     */
    protected $_plugins = array();

    /**
     * Events that this component listens on.
     *
     * @var array An array that contains list of events.
     */
    protected $_listenedEvents = array(
        iMSCP_Events::onAdminScriptEnd,
        iMSCP_Events::onResellerScriptEnd,
        iMSCP_Events::onClientScriptEnd,
        iMSCP_Events::onLoginScriptEnd,
        iMSCP_Events::onOrderPanelScriptEnd
    );

    /**
     * Constructor.
     *
     * @throws iMSCP_Debug_Bar_Exception
     * @param iMSCP_Events_Manager $eventsManager
     * @param string|array $plugins Plugin(s) instance(s).
     */
    public function __construct(iMSCP_Events_Manager $eventsManager, $plugins)
    {
        $this->_enventsManager = $eventsManager;

        // Creating i-MSCP Version Tab always shown
        $this->_plugins[] = new iMSCP_Debug_Bar_Plugin_Version();

        $i = 998;
        foreach ((array)$plugins as $plugin) {
            if (!$plugin instanceof iMSCP_Debug_Bar_Plugin_Interface) {
                throw new iMSCP_Debug_Bar_Exception(
                    'All plugins for the debug bar must implement the ' .
                    'iMSCP_Debug_Bar_Plugin_Interface interface.');
            } else {
                $this->registerListener($plugin, $i);
                $this->_plugins[] = $plugin;
            }

            $i--;
        }

        $eventsManager->registerListener($this->_listenedEvents, $this, 999);
    }

    /**
     * Register a plugin listener on the events manager.
     *
     * @param  iMSCP_Debug_Bar_Plugin_Interface $plugin Plugin instance.
     * @param  int $stackIndex Order in which listeners methods will be executed.
     * @return void
     */
    protected function registerListener($plugin, $stackIndex)
    {
        $this->_enventsManager->registerListener(
            $plugin->getListenedEvents(), $plugin, $stackIndex);
    }

    /**
     * Catch all calls for listener methods of this class to avoid to declarate them
     * since they do same job.
     *
     * @param string $listenerMethod Listener method
     * @param iMSCP_Events_Event $event Event object
     */
    public function __call($listenerMethod, $event)
    {
        if (!in_array($listenerMethod, $this->_listenedEvents)) {
            throw new iMSCP_Debug_Bar_Exception('Unknown listener method.');
        }

        $this->_event = $event[0];
        $this->buidDebugBar();
    }

    /**
     * Builds the Debug Bar and adds it to the repsonse.
     *
     * @return void
     */
    protected function buidDebugBar()
    {
        // Doesn't act on AJAX request.
        if (is_xhr()) {
            return;
        }

        $xhtml = '';

        /** @var $plugin iMSCP_Debug_Bar_Plugin_Interface */
        foreach ($this->_plugins as $plugin)
        {
            $panel = $plugin->getPanel();
            if ($panel == '') {
                continue;
            }

            $xhtml .= '<div id="iMSCPdebug_' . $plugin->getIdentifier()
                   . '" class="iMSCPdebug_panel">' . $panel . '</div>';
        }

        foreach ($this->_plugins as $plugin) {
            $tab = $plugin->getTab();

            if ($tab == '') {
                continue;
            }

            $xhtml .= '<span class="iMSCPdebug_span clickable" onclick="iMSCPdebugPanel(\'iMSCPdebug_' .
                      $plugin->getIdentifier() . '\');">';
            $xhtml .= '<img src="' . $plugin->getIcon() .
                      '" style="vertical-align:middle" alt="'
                      . $plugin->getIdentifier() .
                      '" title="' . $plugin->getIdentifier() . '" /> ';
            $xhtml .= $tab . '</span>';
        }

        $xhtml .= '<span class="iMSCPdebug_span iMSCPdebug_last clickable" id="iMSCPdebug_toggler" onclick="iMSCPdebugSlideBar()">&#171;</span>';
        $xhtml .= '</div>';

        $templateEngine = $this->_event->getTemplateEngine();
        $response = $templateEngine->getLastParseResult();
        $response = preg_replace('@(</head>)@i', $this->_buildHeader() . PHP_EOL . '$1', $response);
        $response = str_ireplace('</body>','<div id="iMSCPdebug_debug">' . $xhtml . '</div></body>', $response);
        $templateEngine->replaceLastParseResult($response);
    }

    /**
     * Returns xhtml header for the Debug Bar.
     *
     * @return string
     */
    protected function _buildHeader()
    {
        $collapsed = isset($_COOKIE['iMSCPdebugCollapsed'])
            ? $_COOKIE['iMSCPdebugCollapsed'] : 0;

        return ('
            <style type="text/css" media="screen">
                #iMSCPdebug_debug { font: 11px/1.4em Lucida Grande, Lucida Sans Unicode, sans-serif; position:fixed; bottom:0px; left:0px; color:#000; z-index: 255;}
                #iMSCPdebug_debug ol {margin:10px 0px; padding:0 25px}
                #iMSCPdebug_debug li {margin:0 0 10px 0;}
                #iMSCPdebug_debug .clickable {cursor:pointer}
                #iMSCPdebug_toggler { font-weight:bold; background:#BFBFBF; }
                .iMSCPdebug_span { border: 1px solid #999; border-right:0px; background:#DFDFDF; padding: 5px 5px; }
                .iMSCPdebug_last { border: 1px solid #999; }
                .iMSCPdebug_panel { text-align:left; position:absolute;bottom:21px;width:600px; max-height:400px; overflow:auto; display:none; background:#E8E8E8; padding:5px; border: 1px solid #999; }
                .iMSCPdebug_panel .pre {font: 11px/1.4em Monaco, Lucida Console, monospace; margin:0 0 0 22px}
                #iMSCPdebug_exception { border:1px solid #CD0A0A;display: block; }
            </style>
            <script type="text/javascript">
                if (typeof jQuery == "undefined") {
                    var scriptObj = document.createElement("script");
                    scriptObj.src = "http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js";
                    scriptObj.type = "text/javascript";
                    var head=document.getElementsByTagName("head")[0];
                    head.insertBefore(scriptObj,head.firstChild);
                }
                var iMSCPdebugLoad = window.onload;
                window.onload = function(){
                    if (iMSCPdebugLoad) {
                        iMSCPdebugLoad();
                    }
                    //jQuery.noConflict();
                    iMSCPdebugCollapsed();
                };

                function iMSCPdebugCollapsed() {
                    if (' . $collapsed . ' == 1) {
                        iMSCPdebugPanel();
                        jQuery("#iMSCPdebug_toggler").html("&#187;");
                        return jQuery("#iMSCPdebug_debug").css("left", "-"+parseInt(jQuery("#iMSCPdebug_debug").outerWidth()-jQuery("#iMSCPdebug_toggler").outerWidth()+1)+"px");
                    }
                }

                function iMSCPdebugPanel(name) {
                    jQuery(".iMSCPdebug_panel").each(function(i){
                        if(jQuery(this).css("display") == "block") {
                            jQuery(this).slideUp();
                        } else {
                            if (jQuery(this).attr("id") == name)
                                jQuery(this).slideDown();
                            else
                                jQuery(this).slideUp();
                        }
                    });
                }

                function iMSCPdebugSlideBar() {
                    if (jQuery("#iMSCPdebug_debug").position().left >= 0) {
                        document.cookie = "iMSCPdebugCollapsed=1;expires=;path=/";
                        iMSCPdebugPanel();
                        jQuery("#iMSCPdebug_toggler").html("&#187;");
                        return jQuery("#iMSCPdebug_debug").animate({left:"-"+parseInt(jQuery("#iMSCPdebug_debug").outerWidth()-jQuery("#iMSCPdebug_toggler").outerWidth()+1)+"px"}, "normal", "swing");
                    } else {
                        document.cookie = "iMSCPdebugCollapsed=0;expires=;path=/";
                        jQuery("#iMSCPdebug_toggler").html("&#171;");
                        return jQuery("#iMSCPdebug_debug").animate({left:"0px"}, "normal", "swing");
                    }
                }

                function iMSCPdebugToggleElement(name, whenHidden, whenVisible){
                    if(jQuery(name).css("display")=="none"){
                        jQuery(whenVisible).show();
                        jQuery(whenHidden).hide();
                    } else {
                        jQuery(whenVisible).hide();
                        jQuery(whenHidden).show();
                    }
                    jQuery(name).slideToggle();
                }
            </script>');
    }
}
