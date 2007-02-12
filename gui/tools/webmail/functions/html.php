<?php

/**
 * html.php
 *
 * The idea is to inlcude here some functions to make easier
 * the right to left implementation by "functionize" some
 * html outputs.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: html.php,v 1.14.2.8 2006/04/14 22:27:07 jervfors Exp $
 * @package squirrelmail
 * @since 1.3.0
 */

/**
 * Generate html tags
 *
 * @param string $tag Tag to output
 * @param string $val Value between tags
 * @param string $align Alignment (left, center, etc)
 * @param string $bgcolor Back color in hexadecimal
 * @param string $xtra Extra options
 * @return string HTML ready for output
 */
function html_tag( $tag,                // Tag to output
                       $val = '',           // Value between tags
                       $align = '',         // Alignment
                       $bgcolor = '',       // Back color
                       $xtra = '' ) {       // Extra options

        GLOBAL $languages, $squirrelmail_language;

        $align = strtolower( $align );
        $bgc = '';
        $tag = strtolower( $tag );

        if ( isset( $languages[$squirrelmail_language]['DIR']) ) {
            $dir = $languages[$squirrelmail_language]['DIR'];
        } else {
            $dir = 'ltr';
        }

        if ( $dir == 'ltr' ) {
            $rgt = 'right';
            $lft = 'left';
        } else {
            $rgt = 'left';
            $lft = 'right';
        }

        if ( $bgcolor <> '' ) {
            $bgc = " bgcolor=\"$bgcolor\"";
        }

        switch ( $align ) {
            case '':
                $alg = '';
                break;
            case 'right':
                $alg = " align=\"$rgt\"";
                break;
            case 'left':
                $alg = " align=\"$lft\"";
                break;
            default:
                $alg = " align=\"$align\"";
                break;
        }

        $ret = "<$tag";

        if ( $dir <> 'ltr' ) {
            $ret .= " dir=\"$dir\"";
        }
        $ret .= $bgc . $alg;

        if ( $xtra <> '' ) {
            $ret .= " $xtra";
        }

        if ( $val <> '' ) {
            $ret .= ">$val</$tag>\n";
        } else {
            $ret .= '>' . "\n";
        }

        return( $ret );
    }

    /* handy function to set url vars */
    /* especially usefull when $url = $PHP_SELF */
    function set_url_var($url, $var, $val=0, $link=true) {
        $k = '';
        $ret = '';
        $pat_a = array (
                       '/.+(\\&'.$var.')=(.*)\\&/AU',   /* in the middle */
                       '/.+\\?('.$var.')=(.*\\&).+/AU', /* at front, more follow */
                       '/.+(\\?'.$var.')=(.*)$/AU',     /* at front and only var */
                       '/.+(\\&'.$var.')=(.*)$/AU'      /* at the end */
                     );
	preg_replace('/&amp;/','&',$url);
        switch (true) {
            case (preg_match($pat_a[0],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            case (preg_match($pat_a[1],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            case (preg_match($pat_a[2],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            case (preg_match($pat_a[3],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            default:
                if ($val) {
                    if (strpos($url,'?')) {
                        $url .= "&$var=$val";
                    } else {
                        $url .= "?$var=$val";
                    }
                }
                break;
        }

        if ($k) {
            if ($val) {
                $rpl = "$k=$val";
		if ($link) {
		    $rpl = preg_replace('/&/','&amp;',$rpl);
		}
            } else {
                $rpl = '';
            }
            if( substr($v,-1)=='&' ) {
                $rpl .= '&';
            }
            $pat = "/$k=$v/";
            $url = preg_replace($pat,$rpl,$url);
        }
        return $url;
    }

    /* Temporary test function to proces template vars with formatting.
     * I use it for viewing the message_header (view_header.php) with
     * a sort of template.
     */
    function echo_template_var($var, $format_ar = array() ) {
        $frm_last = count($format_ar) -1;

        if (isset($format_ar[0])) echo $format_ar[0];
            $i = 1;

        switch (true) {
            case (is_string($var)):
                echo $var;
                break;
            case (is_array($var)):
                $frm_a = array_slice($format_ar,1,$frm_last-1);
                foreach ($var as $a_el) {
                    if (is_array($a_el)) {
                        echo_template_var($a_el,$frm_a);
                    } else {
                        echo $a_el;
                        if (isset($format_ar[$i])) {
                            echo $format_ar[$i];
                        }
                        $i++;
                    }
                }
                break;
            default:
                break;
        }
        if (isset($format_ar[$frm_last]) && $frm_last>$i ) {
            echo $format_ar[$frm_last];
        }
    }
?>