<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2004 be moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



class pTemplate {

    var $tpl_name;
    var $tpl_data;
    var $tpl_options;

    var $dtpl_name;
    var $dtpl_data;
    var $dtpl_options;
    var $dtpl_values;

    var $namespace;

    var $root_dir;

    var $tpl_start_tag;
    var $tpl_end_tag;
    var $tpl_start_tag_name;
    var $tpl_end_tag_name;
    var $tpl_name_rexpr;

    var $tpl_start_rexpr;
    var $tpl_end_rexpr;

    var $last_parsed;

    var $stack;
    var $sp;

    function pTemplate($r_dir = '') {

        $this -> tpl_name = array();
        $this -> tpl_data = array();
        $this -> tpl_options = array();

        $this -> dtpl_name = array();
        $this -> dtpl_data = array();
        $this -> dtpl_options = array();
        $this -> dtpl_values = array();

        $this -> namespace = array();

        if ($r_dir) {

            $this -> set_root($r_dir);

        } else {

            $this -> set_root();

        }

        $this -> tpl_start_tag = '<!-- ';
        $this -> tpl_end_tag = ' -->';
        $this -> tpl_start_tag_name = 'BDP: ';
        $this -> tpl_end_tag_name = 'EDP: ';
        $this -> tpl_name_rexpr = '([a-z0-9][a-z0-9\_]*)';

        $this -> tpl_start_rexpr = '/';
        $this -> tpl_start_rexpr .= $this -> tpl_start_tag;
        $this -> tpl_start_rexpr .= $this -> tpl_start_tag_name;
        $this -> tpl_start_rexpr .= $this -> tpl_name_rexpr;
        $this -> tpl_start_rexpr .= $this -> tpl_end_tag . '/' ;

        $this -> tpl_end_rexpr = '/';
        $this -> tpl_end_rexpr .= $this -> tpl_start_tag;
        $this -> tpl_end_rexpr .= $this -> tpl_end_tag_name;
        $this -> tpl_end_rexpr .= $this -> tpl_name_rexpr;
        $this -> tpl_end_rexpr .= $this -> tpl_end_tag . '/';

        $this -> last_parsed = '';

        $this -> stack = array();
        $this -> sp = 0;
    }

    function set_root($set_dir = '.') {

        $this -> root_dir = $set_dir;

    }

    function assign($nsp_name, $nsp_data = '') {

        if (gettype($nsp_name) == "array") {

            foreach ($nsp_name as $key => $value) {

                $this -> namespace[$key] = $value;

            }

        } else {

            $this -> namespace[$nsp_name] = $nsp_data;

        }
    }

    function unsign($nsp_name) {

        if (gettype($nsp_name) == "array") {

            foreach ($nsp_name as $key => $value) {

                unset($this -> namespace[$key]);

            }

        } else {

            unset($this -> namespace[$nsp_name]);

        }
    }

    function define($t_name, $t_value = '') {

        if (gettype($t_name) == "array") {

            foreach ($t_name as $key => $value) {

                $this -> tpl_name[$key] = $value;
                $this -> tpl_data[$key] = '';
                $this -> tpl_options[$key] = '';

            }

        } else {

            $this -> tpl_name[$t_name] = $t_value;
            $this -> tpl_data[$t_name] = '';
            $this -> tpl_options[$t_name] = '';

        }
    }

    function define_dynamic($t_name, $t_value = '') {
        if (gettype($t_name) == "array") {
        foreach ($t_name as $key => $value) {
            $this -> dtpl_name[$key] = $value;
            $this -> dtpl_data[$key] = '';
            $this -> dtpl_options[$key] = '';
        }
        } else {
            $this -> dtpl_name[$t_name] = $t_value;
            $this -> dtpl_data[$t_name] = '';
            $this -> dtpl_options[$t_name] = '';
        }
    }

    function define_no_file($t_name, $t_value = '') {
        if (gettype($t_name) == "array") {
        foreach ($t_name as $key => $value) {
            $this -> tpl_name[$key] = '_no_file_';
            $this -> tpl_data[$key] = $value;
            $this -> tpl_options[$key] = '';
        }
        } else {
            $this -> tpl_name[$t_name] = '_no_file_';
            $this -> tpl_data[$t_name] = $t_value;
            $this -> tpl_options[$t_name] = '';
        }
    }

    function define_no_file_dynamic($t_name, $t_value = '') {

        if (gettype($t_name) == "array") {

            foreach ($t_name as $key => $value) {

                $this -> dtpl_name[$key] = '_no_file_';

                $this -> dtpl_data[$key] = $value;

                $this -> dtpl_data[strtoupper($key)] = $value;

                $this -> dtpl_options[$key] = '';

            }

        } else {

            $this -> dtpl_name[$t_name] = '_no_file_';

            $this -> dtpl_data[$t_name] = $t_value;

            $this -> dtpl_data[strtoupper($t_name)] = @$value;

            $this -> dtpl_options[$t_name] = '';

        }

    }

    function find_next($data, $spos)
    {

        do {

            $tag_spos = strpos($data, $this -> tpl_start_tag, $spos + 1);

            if (gettype($tag_spos) == 'boolean') {

                return false;

            }

            $tag_epos = strpos($data, $this -> tpl_end_tag, $tag_spos + 1);

            if (gettype($tag_epos) == 'boolean') {

                return false;

            }

            $length = $tag_epos + strlen($this -> tpl_end_tag) - $tag_spos;

            $tag = substr($data, $tag_spos, $length);

            if ($tag) {

                if (preg_match($this -> tpl_start_rexpr, $tag, $matches)) {

                    return array($matches[1], 'b', $tag_spos, $tag_epos + strlen($this -> tpl_end_tag) - 1);

                } else if (preg_match($this -> tpl_end_rexpr, $tag, $matches)) {

                    return array($matches[1], 'e', $tag_spos, $tag_epos + strlen($this -> tpl_end_tag) - 1);

                } else {

                    $spos = $tag_epos;

                }

            } else {

                return false;

            }

        }  while (true);

    }
        
    function find_next_curl($data, $spos)
    {

        $curl_b = strpos($data, '{', $spos + 1);

        $curl_e = strpos($data, '}', $spos + 1);

        if ($curl_b) {

            if ($curl_e) {

                if ($curl_b < $curl_e) {

                    return array('{', $curl_b);

                } else {

                    return array('}', $curl_e);

                }

            } else {

                return array('{', $curl_b);

            }

        } else {

            if ($curl_e) {

                return array('}', $curl_e);

            } else {

                return false;

            }
        }

    }

    function devide_dynamic($data)
    {

        $start_from = -1;

        $tag = $this -> find_next($data, $start_from);

        while ($tag)  {

            if ($tag[1] == 'b') {

                $this -> stack[$this -> sp++] = $tag;

                $start_from = $tag[3];

            } else {

                $tpl_name = $tag[0];

                $tpl_eb_pos = $tag[2]; $tpl_ee_pos = $tag[3];

                $tag = $this -> stack [--$this -> sp];

                $tpl_bb_pos = $tag[2]; $tpl_be_pos = $tag[3];

                $this -> dtpl_data[strtoupper($tpl_name)] = substr($data, $tpl_be_pos + 1, $tpl_eb_pos - $tpl_be_pos - 1);

                $this -> dtpl_data[$tpl_name] = substr($data, $tpl_be_pos + 1, $tpl_eb_pos - $tpl_be_pos - 1);

                $data = substr_replace($data, "{".strtoupper($tpl_name)."}", $tpl_bb_pos, $tpl_ee_pos - $tpl_bb_pos + 1);

                $start_from = $tpl_bb_pos + strlen("{".$tpl_name."}") - 1;
            }

            $tag = $this -> find_next($data, $start_from);

        }

        return $data;

    }

    function substitute_dynamic($data)
    {

        $this -> sp = 0;

        $start_from = -1;

        $curl_b = substr($data, '{', $start_from);

        if ($curl_b) {

            $this -> stack[$this -> sp++] = array('{', $curl_b);

            $curl = $this -> find_next_curl($data, $start_from);

            while ($curl) {

                if ($curl[0] == '{') {

                    $this -> stack[$this -> sp++] = $curl;

                    $start_from = $curl[1];

                } else {

                    $curl_e = $curl[1];

                    if ($this -> sp > 0) {

                        $curl = $this -> stack [--$this -> sp]; /* CHECK for empty stack must be done HERE ! */

                        $curl_b = $curl[1];

                        if ($curl_b < $curl_e + 1) {

                            $var_name = substr($data, $curl_b + 1, $curl_e  - $curl_b - 1);

                            /*
                             *
                             * The whole WORK goes here :) ;
                             *
                             */

                            if (preg_match('/[A-Z0-9][A-Z0-9\_]*/', $var_name)) {

                                 if (isset($this -> namespace[$var_name])) {

                                    $data = substr_replace($data, $this -> namespace[$var_name], $curl_b, $curl_e - $curl_b + 1);

                                    $start_from = $curl_b - 1; /* new value may also begin with '{' */

                                 } else if (isset($this -> dtpl_data[$var_name])) {

                                    $data = substr_replace($data, $this -> dtpl_data[$var_name], $curl_b, $curl_e - $curl_b + 1);

                                    $start_from = $curl_b - 1; /* new value may also begin with '{' */

                                 } else {

                                    $start_from = $curl_b; /* no soutable value found -> go forward */

                                 }

                            } else {

                                $start_from = $curl_b; /* go forward, we have {not varialbe} here :) */

                            }

                        } else {

                            $start_from = $curl_e; /* go forward, we have {} here :) */

                        }

                    } else {

                        $start_from = $curl_e;

                    }
                }

                $curl = $this -> find_next_curl($data, $start_from);

            }

            return $data;

        } else {

            return $data; /* tghere is nothing to substitute in $data */

        }
    }

    function is_safe($fname)
    {

        if (file_exists(($this -> root_dir).'/'.$fname)) {

            return true;

        }
        
        return false;
    }

    function get_file($fname)
    {

        if ($this -> is_safe($fname)) {

            if (!($fp = fopen(($this -> root_dir).'/'.$fname, 'r'))) {

                return '';

            }

            $res = fread($fp, filesize(($this -> root_dir).'/'.$fname));

            if (!$res) {

                return '';

            }

            fclose($fp);

            return $res;

        }

        return '';

    }

    function find_origin($tname)
    {

        if (!@$this -> dtpl_name[$tname]) {

            return false;

        }

        while (
        	!preg_match('/\.[Tt][Pp][Ll]/', $this -> dtpl_name[$tname]) &&
        	!preg_match('/_no_file_/', $this -> dtpl_name[$tname])
              ) {

            $tname = $this -> dtpl_name[$tname];

        }

        return $tname;
    }

    function parse_dynamic($pname, $tname, $ADD_FLAG)
    {

        $CHILD = false; $parent = ''; $swap = '';

        if (
             !preg_match('/\.[Tt][Pp][Ll]/', @$this -> dtpl_name[$tname]) &&
             !preg_match('/_no_file_/', @$this -> dtpl_name[$tname])
           ) {

            $CHILD = true;

            $parent = $this -> find_origin($tname);

            if (!$parent) {

                return false;

            }

        }

        if ($CHILD) {

            $swap = $parent; $parent = $tname; $tname = $swap;

        }

        if (!@$this -> dtpl_data[$tname]) {

            @$this -> dtpl_data[$tname] = $this -> get_file(@$this -> dtpl_name[$tname]);

        }

        if (!preg_match('/d\_/', @$this -> dtpl_options[$tname])) {

            @$this -> dtpl_options[$tname] .= 'd_';

            $tpl_origin = @$this -> dtpl_data[$tname];

            @$this -> dtpl_data[$tname] = $this -> devide_dynamic($tpl_origin);

        }

        if ($CHILD) {

            $swap = $parent; $parent = $tname; $tname = $swap;

        }

        if ($ADD_FLAG) {

            $safe = @$this -> namespace[$pname];

            $this -> namespace[$pname] = $safe.($this -> substitute_dynamic($this -> dtpl_data[$tname], $ADD_FLAG));

        } else {

            $this -> namespace[$pname] = $this -> substitute_dynamic($this -> dtpl_data[$tname], $ADD_FLAG);

        }

        return true;

    }

    function parse($pname, $tname)
    {

        if (!preg_match('/[A-Z0-9][A-Z0-9\_]*/', $pname)) {

            return $false;

        }

        if (!preg_match('/[A-Za-z0-9][A-Za-z0-9\_]*/', $tname)) {
        
           return $false;
        
        }

        $ADD_FLAG = false;

        if (preg_match('/^\./', $tname)) {

            $tname = substr($tname, 1);

            $ADD_FLAG = true;

        }

        if (@$this -> tpl_name[$tname] == '_no_file_' || preg_match('/\.[Tt][Pp][Ll]/', @$this -> tpl_name[$tname]) ) { /* static NO FILE */  /* static FILE */

            if (@$this -> tpl_data[$tname] == '') {

                $this -> tpl_data[$tname] = $this -> get_file($this -> tpl_name[$tname]);

            }

            if ($ADD_FLAG) {

                @$this -> namespace[$pname] .= $this -> substitute_dynamic($this -> tpl_data[$tname]);

            } else {

                $this -> namespace[$pname] = $this -> substitute_dynamic($this -> tpl_data[$tname]);

            }

            $this -> last_parsed = $this -> namespace[$pname];

        } else if (@$this -> dtpl_name[$tname] == '_no_file_' || preg_match('/\.[Tt][Pp][Ll]/', @$this -> dtpl_name[$tname]) || $this -> find_origin($tname)) { /* dynamic NO FILE */ /* dynamic FILE */

            $dres = $this -> parse_dynamic($pname, $tname, $ADD_FLAG);

            if (!$dres) {

                return $dres;

            }

            $this -> last_parsed = $this -> namespace[$pname];

        } else {

            if ($ADD_FLAG) {

                @$this -> namespace[$pname] .= $this -> namespace[$tname];

            } else {

                $this -> namespace[$pname] = $this -> namespace[$tname];

            }

        }

    }

    function prnt($pname = '')
    {

        if ($pname) {

            print @$this -> namespace[$pname];

        } else {

            print @$this -> last_parsed;

        }

    }

    function FastPrint($pname = '')
    {

        if ($pname) {

            $this -> prnt($pname);

        } else {

            $this -> prnt();

        }

    }

    /* functions added for backward compatibility and debugging */

    function strict()
    {

    }

    function no_strict()
    {

    }

    function show_unknown()
    {

    }

    function print_namespace()
    {

        print "<br><u>'namespace' contents</u><br>";

        foreach($this -> namespace as $key => $value) {

            print "$key => $value<br>";

        }
    }

    function print_tpl_name()
    {

        print "<br><u>'tpl_name' contents</u><br>";

        foreach($this -> tpl_name as $key => $value) {

            print "$key => $value<br>";

        }

    }

    function print_dtpl_name()
    {

        print "<br><u>'dtpl_name' contents</u><br>";

        foreach($this -> dtpl_name as $key => $value) {

            print "$key => $value<br>";

        }

    }

    function print_tpl_data()
    {

        print "<br><u>'tpl_data' contents</u><br>";

        foreach($this -> tpl_data as $key => $value) {

            print "$key => $value<br>";

        }

    }

    function print_dtpl_data()
    {

        print "<br><u>'dtpl_data' contents</u><br>";

        foreach($this -> dtpl_data as $key => $value) {

            print "$key => $value<br>";

        }

    }

    function print_dtpl_options()
    {

        print "<br><u>'dtpl_options' contents</u><br>";

        foreach($this -> dtpl_options as $key => $value) {

            print "$key => $value<br>";

        }

    }

    function print_dtpl_values()
    {

        print "<br><u>'dtpl_values' contents</u><br>";

        foreach($this -> dtpl_values as $key => $value) {

            print "$key => $value<br>";

        }

    }
}

?>
