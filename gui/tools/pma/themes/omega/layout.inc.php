<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * configures general layout
 * for detailed layout configuration please refer to the css files
 *
 * @version $Id: layout.inc.php 10515 2007-07-22 16:22:54Z lem9 $
 * @package phpMyAdmin-theme
 * @subpackage Original
 */

/**
 * We don't need these values, but let's define them, 
 * so PHP doesn't complain... 
 */ 
 $GLOBALS['cfg']['BgcolorOne']               = '';
 $GLOBALS['cfg']['BgcolorTwo']               = '';
 $GLOBALS['cfg']['LeftBgColor']              = '';
 $GLOBALS['cfg']['LeftPointerColor']         = '';
 $GLOBALS['cfg']['RightBgColor']             = '';
 $GLOBALS['cfg']['ThBgcolor']                = '';

/** 
 * navi frame
 */
// navi frame width
$GLOBALS['cfg']['NaviWidth']                = 250;

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']                = '#FFFFFF';

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviBorderColor']          = '#535353';

// background for the navi frame
$GLOBALS['cfg']['NaviBackground']           = '';

// foreground (text) color of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerColor']         = '#f4dc6b';
// background of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerBackground']    = '';
// text color of the selected database name (when showing the table list)
$GLOBALS['cfg']['NaviDatabaseNameColor']    = '#f4dc6b';

/**
 * main frame
 */
// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor']                = '#000000';

// background for the main frame
$GLOBALS['cfg']['MainBackground']           = '#F5F5F5';
//$GLOBALS['cfg']['MainBackground']       = '#F5F5F5 url(' . $_SESSION['PMA_Theme']->getImgPath() . 'vertical_line.png) repeat-y';

// foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor']       = '#000000';

// background of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBackground']  = '#CCFFCC';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';

// background of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBackground']   = '#FFCC99';

/**
 * fonts
 */
/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily']           = 'sans-serif';
/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed']      = 'monospace';

/**
 * tables
 */
// border
$GLOBALS['cfg']['Border']               = 0;
// table header and footer color
$GLOBALS['cfg']['ThBackground']         = '#444444';
// table header and footer background
$GLOBALS['cfg']['ThColor']              = '#000000';
// table data row background
$GLOBALS['cfg']['BgOne']                = '';
// table data row background, alternate
$GLOBALS['cfg']['BgTwo']                = '';

/**
 * query window
 */
// Width of Query window
$GLOBALS['cfg']['QueryWindowWidth']     = 600;
// Height of Query window
$GLOBALS['cfg']['QueryWindowHeight']    = 400;

/**
 * SQL Parser Settings
 * Syntax colouring data
 */
$GLOBALS['cfg']['SQP']['fmtColor']      = array(
    'comment'            => '#808000',
    'comment_mysql'      => '',
    'comment_ansi'       => '',
    'comment_c'          => '',
    'digit'              => '',
    'digit_hex'          => 'teal',
    'digit_integer'      => 'teal',
    'digit_float'        => 'aqua',
    'punct'              => 'fuchsia',
    'alpha'              => '',
    'alpha_columnType'   => '#FF9900',
    'alpha_columnAttrib' => '#0000FF',
    'alpha_reservedWord' => '#990099',
    'alpha_functionName' => '#FF0000',
    'alpha_identifier'   => 'black',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'              => '#008000',
    'quote_double'       => '',
    'quote_single'       => '',
    'quote_backtick'     => ''
);
?>
