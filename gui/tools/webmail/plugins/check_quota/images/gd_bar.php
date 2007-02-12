<?php

/*
 * This file is originally donated by Mike Smith <mike@ftl.com>
 * Some modifications and simplifications by Kerem Erkan
*/


if (!defined('SM_PATH'))
   define('SM_PATH', '../../../');

include_once(SM_PATH . 'include/validate.php');

include(SM_PATH . 'plugins/check_quota/config.php');

$width = $_GET["width"];
$usage = $_GET["usage"];
$threshold = $_GET["threshold"];

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

header ('Content-type: image/png');
    
$im = @imagecreate ($width, 15)
	or die ('Cannot Initialize new GD image stream!');

$white = imagecolorallocate ($im, 255, 255, 255);
$green = imagecolorallocate ($im, 0, 128, 0);
$yellow = imagecolorallocate ($im, 252, 219, 29);
$red = imagecolorallocate ($im, 215, 0, 0);

$percent = ($usage/$threshold);
$eval = round($percent*100, 1);

switch(TRUE) 
{

	case ( $eval < $cq_yellow_alert_percent) :
		$fill_width = round($width * $percent);
		$fill_color = $green;
		break;

	case ( ($eval < $cq_red_alert_percent) && ($eval >= $cq_yellow_alert_percent) ) :
		$fill_width = round($width * $percent);
		$fill_color = $yellow;
		break;

	case ( $eval >= 100 ) :
		$eval = 100;
		$fill_width = $width - 1;
		$fill_color = $red;
		break;

	default:
		$fill_width = round($width * $percent);
		$fill_color = $red;
		break;

}

imagefilledrectangle ($im, 0, 0, $fill_width, 14, $fill_color);
imagepng ($im);

?>
