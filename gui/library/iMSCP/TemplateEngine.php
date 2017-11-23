<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
     * @var array Runtime variables
     */
    protected $runtimeVariables = [];

    /**
     * @var array List of parent templates that were already parsed
     */
    protected $seenParents = [];

    /**
     * TemplateEngine constructor.
     *
     * @param string $tpldir Templates root directory
     * @param EventsManagerInterface|NULL $em
     */
    public function __construct($tpldir = NULL, EventsManagerInterface $em = NULL)
    {
        $this->tplDir = utils_normalizePath($tpldir ?: Registry::get('config')['ROOT_TEMPLATE_PATH']);
        $this->em = $em ?: Registry::get('iMSCP_Application')->getEventsManager();
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
            $this->runtimeVariables[$varnames] = $value;
            return;
        }

        $this->runtimeVariables = array_replace($this->runtimeVariables, $varnames);
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
            unset($this->runtimeVariables[$varnames]);
            return;
        }

        foreach ($varnames as $varname)
            unset($this->runtimeVariables[$varname]);
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
        return isset($this->runtimeVariables[$varname]);
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
        } else
            $addFlag = false;

        if (!isset($this->seenParents[$pname = $this->findParent($tname)])) {
            if (NULL === $this->tplData[$pname]) { # Template file case
                $this->tplData[$pname] = $this->loadFile($this->tplName[$pname]);
                $this->interpolateBlocks($this->tplData[$pname]);
            } elseif ($pname != $tname) # Inline template case
                $this->interpolateBlocks($this->tplData[$pname]);

            $this->seenParents[$pname] = 1;
        }

        $this->lastParsedVarname = $varname;

        if ($addFlag && isset($this->runtimeVariables[$varname])) {
            $this->runtimeVariables[$varname] .= $this->substituteVariables($this->tplData[$tname]);
            return;
        }

        $this->runtimeVariables[$varname] = $this->substituteVariables($this->tplData[$tname]);
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

        if (!isset($this->runtimeVariables[$varname]))
            throw new \InvalidArgumentException(sprintf(
                'Unknown `%s` runtime template variable. Did you forgot to call parse()?', $varname
            ));

        echo $this->runtimeVariables[$varname];
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

        if (!isset($this->runtimeVariables[$this->lastParsedVarname]))
            throw new \InvalidArgumentException(sprintf(
                'Unknown `%s` runtime template variable. Did you forgot to call parse()?', $this->lastParsedVarname
            ));

        return $this->runtimeVariables[$this->lastParsedVarname];
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

        if (!isset($this->runtimeVariables[$varname]))
            throw new \InvalidArgumentException(sprintf(
                'Unknown `%s` runtime template variable. Did you forgot to call parse()?', $varname
            ));

        $this->runtimeVariables[$varname] = $content;
        return $this;
    }

    /**
     * Load a template file, including its childs (included template files)
     *
     * @param string|array $fname Template file path or an array where the
     *                            second item contains the template file path
     * @return mixed|string
     */
    protected function loadFile($fname)
    {
        static $parentTplDir = NULL;

        if (is_array($fname))
            $fname = ($parentTplDir ?: $parentTplDir) . '/' . $fname[1];

        $prevParentTplDir = $parentTplDir;
        $parentTplDir = dirname($fname);
        $filepath = $this->tplDir . '/' . $fname;

        $this->em->dispatch(Events::onBeforeLoadTemplateFile, [
            'context'      => $this,
            'templatePath' => $filepath
        ]);

        $errLevel = error_reporting(0);
        ob_start();
        $this->run(utils_normalizePath($filepath));
        $fileContent = ob_get_clean();
        error_reporting($errLevel);

        if ($fileContent == '') {
            $error = error_get_last();

            if (empty($error))
                throw new \RuntimeException(sprintf("The %s template file couldn't be loaded.", $filepath));

            throw new \RuntimeException(sprintf(
                "The %s template file couldn't be loaded: %s -- File %s -- Line %d",
                $filepath, $error['message'], $error['file'], $error['line']
            ));
        }

        if (false !== strpos($fileContent, '<!-- INCLUDE '))
            $fileContent = preg_replace_callback('/<!-- INCLUDE "([^\"]+)" -->/m', [$this, 'loadFile'], $fileContent);

        $parentTplDir = $prevParentTplDir;
        return $fileContent;
    }

    /**
     * Includes the given template file in a scope with only public $this variables
     *
     * @param string $scriptPath The view script to execute
     */
    protected function run($scriptPath)
    {
        include $scriptPath;
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
                $startFrom = $curlE; // go ahead, we have {} here.
                $curl = $this->findNextCurl($tpl, $startFrom);
                continue;
            }

            if ('' == $varname = trim(substr($tpl, $curlB + 1, $curlE - $curlB - 1))) {
                $startFrom = $curlE; // go ahead, we have no {\w+} here.
                $curl = $this->findNextCurl($tpl, $startFrom);
                continue;
            }

            if (isset($this->runtimeVariables[$varname])) {
                $tpl = substr_replace($tpl, $this->runtimeVariables[$varname], $curlB, $curlE - $curlB + 1);
                $startFrom = $curlB - 1; // Substitution result can also be a variable
            } elseif (isset($this->tplData[$varname])) {
                $tpl = substr_replace($tpl, $this->tplData[$varname], $curlB, $curlE - $curlB + 1);
                $startFrom = $curlB - 1; // Substitution result can also be a variable
            } else
                $startFrom = $curlE; // no suitable value found -> go ahead

            $curl = $this->findNextCurl($tpl, $startFrom);
        }

        return $tpl;
    }

    /**
     * Find top most parent of the given template or template block
     *
     * @param string $tname Name of template or template block
     * @return string|false
     */
    protected function findParent($tname)
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
     * Interpolate all template blocks within the given template
     *
     * @param string &$tpl Reference to template
     * @return void
     */
    protected function interpolateBlocks(&$tpl)
    {
        $startPos = -1;
        $stackIdx = 0;
        $stack = [];

        while (strlen($tpl) > $startPos && $tag = $this->findNextTag($tpl, ++$startPos)) {
            if ($tag[1] == 'B') {
                $startPos = $tag[3];
                $stack[$stackIdx++] = $tag;
                continue;
            }

            $tagPrev = $stack[--$stackIdx];
            $blockNameUpper = strtoupper($tag[0]);
            $this->tplData[$blockNameUpper] = substr($tpl, $tagPrev[3], $tag[2] - $tagPrev[3]);
            $this->tplData[$tag[0]] =& $this->tplData[$blockNameUpper];
            $tpl = substr_replace($tpl, '{' . $blockNameUpper . '}', $tagPrev[2], $tag[3] - $tagPrev[2]);
            $startPos = $tagPrev[2] + strlen('{' . $blockNameUpper . '}');
        }
    }

    /**
     * Find the next block tag in the given template
     *
     * @param string $tpl Template
     * @param int $startPos Position from which search must start
     * @return array|false An array containing block tag info
     *                     (tag type, tag name, tag start position, tag end
     *                     position), FALSE if no block tag is found
     */
    protected function findNextTag($tpl, $startPos)
    {
        while (true) {
            if (false === $startPos = strpos($tpl, '<!-- ', $startPos))
                break;

            if (false === $endPos = strpos($tpl, ' -->', $startPos))
                break;

            $endPos += 4;
            $tag = substr($tpl, $startPos, $endPos - $startPos);
            if (preg_match('/<!--\040+(B|E)DP:\040+(\w+)\040+-->/', $tag, $m))
                return [$m[2], $m[1], $startPos, $endPos];

            // Not a valid block tag, continue searching...
            if (strlen($tpl) < ++$endPos)
                return false;

            $startPos = ++$endPos;
        }

        return false;
    }
}
