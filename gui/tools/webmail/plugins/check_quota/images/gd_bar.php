<?php

/**
  * SquirrelMail Check Quota Plugin
  * Copyright(c) 2001-2002 Bill Shupp <hostmaster@shupp.org>
  * Copyright(c) 2002 Claudio Panichi
  * Copyright(c) 2002-2007 Kerem Erkan <kerem@keremerkan.net>
  * Copyright(c) 2003-2007 Paul Lesneiwski <paul@squirrelmail.org>
  * Licensed under the GNU GPL. For full terms see the file LICENSE.
  *
  * GD drawing idea was first submitted by Mike Smith <mike@ftl.com>
  *
  * @package plugins
  * @subpackage check_quota
  *
  */

if (file_exists('../../../include/init.php'))
  include_once('../../../include/init.php');
else if (file_exists('../../../include/validate.php'))
{
  define('SM_PATH', '../../../');
  include_once(SM_PATH . 'include/validate.php');
}
else
{
  chdir('..');
  define('SM_PATH', '../../');
  include_once(SM_PATH . 'src/validate.php');
}


// quota information passed from caller
//
if (!sqGetGlobalVar('w', $width, SQ_GET))
  die('Illegal access');
if (!sqGetGlobalVar('p', $percent, SQ_GET))
  die('Illegal access');
if (!sqGetGlobalVar('t', $type, SQ_GET))
  die('Illegal access');
if (!sqGetGlobalVar('y', $yellow_level, SQ_GET))
  die('Illegal access');
if (!sqGetGlobalVar('r', $red_level, SQ_GET))
  die('Illegal access');


// colors to use in graph - also passed from caller
//
if (!sqGetGlobalVar('c0', $c0, SQ_GET))
  die('Illegal access');
if (!sqGetGlobalVar('c1', $c1, SQ_GET))
  die('Illegal access');
if (!sqGetGlobalVar('c2', $c2, SQ_GET))
  die('Illegal access');
if (!sqGetGlobalVar('c3', $c3, SQ_GET))
  die('Illegal access');


/**
  * <This function needs to be documented>
  *
  * @param <type> $im
  * @param <type> $hex
  *
  * @return <type>
  *
  */
function imagecolorallocatefromhex ($im, $hex)
{
  $int = hexdec($hex);

  return imagecolorallocate ($im,
         0xFF & ($int >> 0x10),
         0xFF & ($int >> 0x8),
         0xFF & $int);
}


// if image cannot be created, GD probably not 
// installed... 
//
$im = @imagecreate ($width, 10)
   or die ('Cannot initialize new GD image stream!');


// send headers to client
//
// HTTP 1.0
header('Pragma: no-cache');         
header('Cache-Control: post-check=0, pre-check=0', false);
// HTTP 1.1
header('Cache-Control: no-store, no-cache, must-revalidate');
// Date in past             
header('Expires: Fri, 01 Jan 1999 01:00:00 GMT');      
// Always modified
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Content-Type: image/' . $type);
    

// calculate image colors
//
$white  = imagecolorallocatefromhex($im, $c0);
$green  = imagecolorallocatefromhex($im, $c1);
$yellow = imagecolorallocatefromhex($im, $c2);
$red    = imagecolorallocatefromhex($im, $c3);


// how much of the graph is green?
//
$green_width = ($width * $percent / 100);
imagefilledrectangle($im, 0, 0, $green_width, 10, $green);


// how much of the graph is yellow?
//
if ( $percent >= $yellow_level )
{
  $yellow_width = ($width * ($percent - $yellow_level) / 100);
  $yellow_start = $width * $yellow_level / 100;
  imagefilledrectangle($im, $yellow_start, 0, $yellow_start + $yellow_width, 10, $yellow);
}


// how much of the graph is red?
//
if ( $percent >= $red_level )
{
  if ( $percent > 100 )
    $percent = 100;
  $red_width = ($width * ($percent - $red_level) / 100);
  $red_start = $width * $red_level / 100;
  imagefilledrectangle($im, $red_start, 0, $red_start + $red_width, 10, $red);
}


// make final image
//
switch ($type)
{
  case('png'):
    imagepng($im);
    break;
  case('gif'):
    imagegif($im);
    break;
  case('jpeg');
    imagejpeg($im);
    break;
}


imagedestroy($im);
