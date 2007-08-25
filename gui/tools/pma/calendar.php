<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id: calendar.php 10474 2007-07-08 17:39:06Z lem9 $
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/header_http.inc.php';
$page_title = $strCalendar;
require './libraries/header_meta_style.inc.php';
?>
<script type="text/javascript" src="./js/tbl_change.js"></script>
<script type="text/javascript">
//<![CDATA[
var month_names = new Array("<?php echo implode('","', $month); ?>");
var day_names = new Array("<?php echo implode('","', $day_of_week); ?>");
var submit_text = "<?php echo $strGo . ' (' . $strTime . ')'; ?>";
//]]>
</script>
</head>
<body onload="initCalendar();">
<div id="calendar_data"></div>
<div id="clock_data"></div>
</body>
</html>
