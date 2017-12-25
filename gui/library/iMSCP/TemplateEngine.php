<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

namespace iMSCP;

use iMSCP_Events as Events;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Registry as Registry;

/**
 * Class TemplateEngine
 * @package iMSCP
 */
class TemplateEngine
{
    /**
     * @var EventsManagerInterface
     */
    protected $em;

    /**
     * @var string Name of runtime variable containing last parse result
     */
    protected $lastParsedVarname;

    /**
     * @var string Templates root directory
     */
    protected $tplDir;

    /**
     * @var array template names
     */
    protected $tplName = [];

    /**
     * @var array template data
     */
    protected $tplData = [];

    /**
     * @var array Template runtime variables
     */
    protected $tplRuntimeVariables = [];

    /**
     * @var array List of resolved templates
     */
    protected $resolvedTemplates = [];

    /** @var \Zend_Cache_Core */
    protected $cache;

    /**
     * TemplateEngine constructor.
     *
     * @param string $tpldir Templates root directory
     * @param EventsManagerInterface|NULL $em
     * @param \Zend_Cache_Core|NULL $cache
     */
    public function __construct($tpldir = NULL, EventsManagerInterface $em = NULL, \Zend_Cache_Core $cache = NULL)
    {
        $this->tplDir = utils_normalizePath($tpldir ?: Registry::get('config')['ROOT_TEMPLATE_PATH']);
        $this->em = $em ?: Registry::get('iMSCP_Application')->getEventsManager();
        $this->cache = $cache ?: Registry::get('iMSCP_Application')->getCache();
    }

    /**
     * Define template file(s) or template block(s)
     *
     * @param string|array $tname Template name or template block name, or an
     *                            array where keys are temlate names or
     *                            template block names, and values are the
     *                            template file paths or names of the templates
     *                            containing the template block
     * @param string $tvalue A temlate file path or name of template containing
     *                       template block. Only relevant if $tname is not an
     *                       array
     * @return void
     */
    public function define($tname, $tvalue = NULL)
    {
        if (!is_array($tname)) {
            $this->tplName[$tname] = $tvalue;
            $this->tplData[$tname] = NULL;
            return;
        }

        foreach ($tname as $key => $value) {
            $this->tplName[$key] = $value;
            $this->tplData[$key] = NULL;
        }
    }

    /**
     * Define inline template(s) or inline template block(s)
     *
     * @param string|array $tname Template name or template block name, or an
     *                            array where keys are temlate names or
     *                            template block names, and values are the
     *                            inline templates or names of the templates
     *                            containing the template block
     * @param string $tvalue An inline temlate or name of template containing
     *                       template block. Only relevant if $tname is not an
     *                       array
     * @return void
     */
    public function define_inline($tname, $tvalue = NULL)
    {
        if (!is_array($tname)) {
            $this->tplName[$tname] = '';
            $this->tplData[$tname] = $tvalue;
            return;
        }

        foreach ($tname as $key => $value) {
            $this->tplName[$key] = '';
            $this->tplData[$key] = $value;
        }
    }

    /**
     * @see define()
     * @param string|array $tname
     * @param string $tvalue
     * @deprecated Make use of the define() method instead
     */
    public function define_dynamic($tname, $tvalue = NULL)
    {
        $this->define($tname, $tvalue);
    }

    /**
     * @see define_inline()
     * @param string|array $tname
     * @param string $tvalue
     * @deprecated Make use of the define_no_file method instead
     */
    public function define_no_file_dynamic($tname, $tvalue = NULL)
    {
        $this->define_inline($tname, $tvalue);
    }

    /**
     * Assign runtime template variable(s)
     *
     * @param string|array $varnames Variables(s)
     * @param string $value
     * @return void
     */
    public function assign($varnames, $value = NULL)
    {
        if (!is_array($varnames)) {
            $this->tplRuntimeVariables[$varnames] = $value;
            return;
        }

        $this->tplRuntimeVariables = array_replace($this->tplRuntimeVariables, $varnames);
    }

    /**
     * Unassign runtime template variable(s)
     *
     * @param string|array $varnames
     * @return void
     */
    public function unsign($varnames)
    {
        if (!is_array($varnames)) {
            unset($this->tplRuntimeVariables[$varnames]);

            if ($this->lastParsedVarname == $varnames)
                $this->lastParsedVarname = NULL;

            return;
        }

        foreach ($varnames as $varname)
            unset($this->tplRuntimeVariables[$varname]);

        if (NULL !== $this->lastParsedVarname && !isset($this->tplRuntimeVariables[$this->lastParsedVarname]))
            $this->lastParsedVarname = NULL;
    }

    /**
     * Is the given variable a template variable?
     *
     * A template variable, unlike a runtime template variable, is a variable
     * that is defined in a template. Template variables are defined
     * statically, or during template block interpolation.
     *
     * @param string $varname Template variable
     * @return boolean TRUE if the given template is a dynamic template, FALSE
     *                 otherwise
     */
    public function isTemplateVariable($varname)
    {
        return isset($this->tplName[$varname]);
    }

    /**
     * Is the given variable a runtime template variable?
     *
     * A runtime template variable, unlike a template variable, is a variable
     * that has not been defined in a template. Those variables are result of
     * variable assignment, template parsing.
     *
     * @param string $varname Variable name
     * @return boolean TRUE if $varname is a template variable name, FALSE otherwise
     */
    public function isRuntimeTemplateVariable($varname)
    {
        return isset($this->tplRuntimeVariables[$varname]);
    }

    /**
     * Parse the given template / block, assigning or adding result to the given
     * runtime variable
     *
     * @param string $varname Name of variable to which parse result will be
     *                        assigned, or added
     * @param string $tname Name of template or block to parse
     */
    public function parse($varname, $tname)
    {
        $this->em->dispatch(Events::onParseTemplate, [
            'varname'        => $varname,
            'tname'          => $tname,
            'templateEngine' => $this
        ]);

        if ($tname[0] == '.') {
            $tname = substr($tname, 1);
            $addFlag = true;
        } else {
            $addFlag = false;
        }

        if (!isset($this->resolvedTemplates[$tname])
            && !isset($this->resolvedTemplates[$pname = $this->findParentTemplate($tname)])
        ) {
            $this->resolveTemplate($pname);
            $this->resolvedTemplates[$tname] = 1;
        }

        if ($addFlag && isset($this->tplRuntimeVariables[$varname]))
            $this->tplRuntimeVariables[$varname] .= $this->substituteVariables($this->tplData[$tname]);
        else
            $this->tplRuntimeVariables[$varname] = $this->substituteVariables($this->tplData[$tname]);

        $this->lastParsedVarname = $varname;
    }

    /**
     * Returns last parse result
     *
     * @return string
     */
    public function getLastParseResult()
    {
        if (NULL === $this->lastParsedVarname)
            throw new \LogicException('Nothing to return. Did you forgot to call parse()?');

        if (!isset($this->tplRuntimeVariables[$this->lastParsedVarname]))
            throw new \InvalidArgumentException(sprintf(
                'Unknown `%s` runtime template variable. Did you forgot to call parse()?', $this->lastParsedVarname
            ));

        return $this->tplRuntimeVariables[$this->lastParsedVarname];
    }

    /**
     * Replaces last parse result with given content
     *
     * @param string $content New content
     * @param string $varname OPTIONAL Name of variable holding last parse result
     * @return TemplateEngine Provides fluent interface, returns self
     */
    public function replaceLastParseResult($content, $varname = NULL)
    {
        if (NULL === $varname) {
            $varname = $this->lastParsedVarname;

            if (NULL === $varname)
                throw new \LogicException('Nothing to replace. Did you forgot to call parse()?');
        }

        if (!isset($this->tplRuntimeVariables[$varname]))
            throw new \InvalidArgumentException(sprintf(
                'Unknown `%s` runtime template variable. Did you forgot to call parse()?', $varname
            ));

        $this->tplRuntimeVariables[$varname] = $content;
        return $this;
    }

    /**
     * Print content of the given runtime template variable
     *
     * @param string $varname
     * @return void
     */
    public function prnt($varname = NULL)
    {
        if (NULL === $varname) {
            if (NULL === $this->lastParsedVarname)
                throw new \LogicException('Nothing to print. Did you forgot to call parse()?');

            $varname = $this->lastParsedVarname;
        }

        if (!isset($this->tplRuntimeVariables[$varname]))
            throw new \InvalidArgumentException(sprintf(
                'Unknown `%s` runtime template variable. Did you forgot to call parse()?', $varname
            ));

        echo $this->tplRuntimeVariables[$varname];
    }

    /**
     * Resolve the given template, including any template block defined into it
     *
     * @param string $tname Template name
     * @return void
     */
    protected function resolveTemplate($tname)
    {
        $id = 'iMSCP_Template' . '_' . md5($_SERVER['SCRIPT_NAME']) . '_' . $tname;

        // Load the template from cache if available
        if (false !== $data = $this->cache->load($id)) {
            $this->tplData = array_merge($this->tplData, $data['tplData']);
            $this->resolvedTemplates = array_merge($this->resolvedTemplates, $data['resolvedTemplates']);
            return;
        }

        if (NULL === $this->tplData[$tname])
            $this->tplData[$tname] = $this->loadTemplateFile($this->tplName[$tname]);

        // Mark the template as resolved
        $this->resolvedTemplates[$tname] = 1;

        // Resolve the template blocks within the template
        $startPos = $stackIdx = 0;
        $stack = [];
        $tpl =& $this->tplData[$tname];
        while (strlen($tpl) > $startPos) {
            if (false === $startPos = strpos($tpl, '<!-- ', $startPos)) break;
            if (false === $endPos = strpos($tpl, ' -->', $startPos)) break;

            $endPos += 4;
            $tag = substr($tpl, $startPos, $endPos - $startPos);

            if (!preg_match('/<!--\040+(?P<tagType>B|E)DP:\040+(?P<tagName>\w+)\040+-->/i', $tag, $m)) {
                // Not a valid block tag, continue searching...
                $startPos = ++$endPos;
                continue;
            }

            if ($m['tagType'] == 'B') {
                // Store begin block tag name and its start/end position for
                // later processing
                $stack[$stackIdx++] = [$m['tagName'], $startPos, $endPos];

                // Update the start position for next search
                $startPos = ++$endPos;
                continue;
            }

            // Retrieve name, start and end position of begin block tag
            list($beginTagName, $beginTagStartPos, $beginTagEndPos) = $stack[--$stackIdx];

            if ($m['tagName'] != $beginTagName)
                throw new \LogicException(sprintf(
                    'Block tag mismatch in the `%s` template:: (%s vs %s).', $tname, $beginTagName, $m['tagName']
                ));

            // Extract the template block content into its own template variable
            $blockName = strtoupper($m['tagName']);
            $this->tplData[$blockName] = substr($tpl, $beginTagEndPos, $startPos - $beginTagEndPos);
            $this->tplData[$m['tagName']] =& $this->tplData[$blockName];

            // Turn the template block into template variable within the template
            $varname = '{' . $blockName . '}';
            $tpl = substr_replace($tpl, $varname, $beginTagStartPos, $endPos - $beginTagStartPos);

            // Mark the template block as resolved
            $this->resolvedTemplates[$m['tagName']] = 1;

            // Update the start position for next search
            $startPos = $beginTagStartPos + strlen($varname);
        }

        // Cache the template
        $this->cache->save(
            [
                'tplData'           => $this->tplData,
                'resolvedTemplates' => $this->resolvedTemplates
            ],
            $id
        );
    }

    /**
     * Find the top most parent of the given template or template block
     *
     * @param string $tname Name of template or template block
     * @return string|false
     */
    protected function findParentTemplate($tname)
    {
        $child = $tname;

        if (!isset($this->tplName[$tname]))
            throw new \LogicException(sprintf("Couldn't find parent. Is the `%s` template/block defined?", $tname));

        if ($this->tplName[$tname] == '')
            $searchInto =& $this->tplData;
        else
            $searchInto =& $this->tplName;

        while (isset($searchInto[$tname])) {
            $child = $tname;
            $tname = $searchInto[$tname];
        }

        if (!isset($searchInto[$tname]))
            $tname = $child;

        return $tname;
    }

    /**
     * Load the given template file, including its childs
     *
     * @param string $fpath Template file path
     * @return string
     */
    protected function loadTemplateFile($fpath)
    {
        $fpath = utils_normalizePath($this->tplDir . '/' . $fpath);
        $this->em->dispatch(Events::onBeforeLoadTemplateFile, [
            'context'      => $this,
            'templatePath' => $fpath
        ]);

        // Turns off error reporting temporarily
        // We do not want the errors as part of template content
        $errLevel = error_reporting(0);
        ob_start();
        $this->run($fpath);
        $fContent = ob_get_clean();
        error_reporting($errLevel);

        if ($fContent == '') {
            if (empty($error = error_get_last()))
                throw new \LengthException(sprintf('The %s template file is emtpy.', $fpath));

            throw new \RuntimeException(sprintf(
                "The %s template file couldn't be loaded: %s -- File %s -- Line %d",
                $fpath, $error['message'], $error['file'], $error['line']
            ));
        }

        // Resolve include tags within the template
        $startPos = 0;
        while (strlen($fContent) > $startPos) {
            if (false === $startPos = strpos($fContent, '<!-- INCLUDE ', $startPos)) break;
            if (false === $endPos = strpos($fContent, ' -->', $startPos)) break;

            $fpath = substr($fContent, $startPos + 13, ($endPos - $startPos) - 13);
            $incFcontent = $this->loadTemplateFile(trim($fpath));
            $fContent = substr_replace($fContent, $incFcontent, $startPos, strlen($fpath) + 17);
            $startPos += strlen($incFcontent) + 1;
        }

        return $fContent;
    }

    /**
     * Includes the given template file in a scope with only public $this variables
     *
     * @param string $fpath The view script to execute
     */
    protected function run($fpath)
    {
        include $fpath;
    }

    /**
     * Substitute variables within the given template
     *
     * @param string $tpl Reference to template
     * @return string Processed template
     */
    protected function substituteVariables($tpl)
    {
        // There are no variables to substitute in the template; return early
        if (false === $curlB = strpos($tpl, '{'))
            return $tpl;

        $startFrom = -1;
        $stackIdx = 0;
        $stack[$stackIdx++] = ['{', $curlB];
        $curl = $this->findNextCurl($tpl, $startFrom);

        while (false !== $curl) {
            if ($curl[0] == '{') {
                $stack[$stackIdx++] = $curl;
                $startFrom = $curl[1];
                $curl = $this->findNextCurl($tpl, $startFrom);
                continue;
            }

            $curlE = $curl[1];

            if ($stackIdx < 1) {
                $startFrom = $curlE;
                $curl = $this->findNextCurl($tpl, $startFrom);
                continue;
            }

            $curl = $stack[--$stackIdx];
            $curlB = $curl[1];

            if ($curlB >= $curlE + 1) {
                $startFrom = $curlE; // we have {} here; go ahead
                $curl = $this->findNextCurl($tpl, $startFrom);
                continue;
            }

            if ('' == $varname = trim(substr($tpl, $curlB + 1, $curlE - $curlB - 1))) {
                $startFrom = $curlE; // we have no valid variable here; go ahead
                $curl = $this->findNextCurl($tpl, $startFrom);
                continue;
            }

            if (isset($this->tplRuntimeVariables[$varname])) {
                $tpl = substr_replace($tpl, $this->tplRuntimeVariables[$varname], $curlB, $curlE - $curlB + 1);
                $startFrom = $curlB - 1; // Substitution result can also be a variable
            } elseif (isset($this->tplData[$varname])) {
                $tpl = substr_replace($tpl, $this->tplData[$varname], $curlB, $curlE - $curlB + 1);
                $startFrom = $curlB - 1; // Substitution result can also be a variable
            } else
                $startFrom = $curlE; // no suitable value found; go ahead

            $curl = $this->findNextCurl($tpl, $startFrom);
        }

        return $tpl;
    }

    /**
     * Finds the next curly bracket within the given template
     *
     * @param string $tpl Template in which curly bracket must be searched
     * @param int $startPos Start search position in $string
     * @return array|bool
     */
    protected function findNextCurl($tpl, $startPos)
    {
        if ($startPos > strlen($tpl))
            return false;

        $curlStartPos = strpos($tpl, '{', ++$startPos);
        $curlEndPos = strpos($tpl, '}', $startPos);

        if (false !== $curlStartPos) {
            if (false !== $curlEndPos) {
                if ($curlStartPos < $curlEndPos)
                    return ['{', $curlStartPos];

                return ['}', $curlEndPos];
            }

            return ['{', $curlStartPos];
        }

        if (false !== $curlEndPos)
            return ['}', $curlEndPos];

        return false;
    }
}
