<?php
/* $Id: text_plain__sql.inc.php 7560 2005-10-16 16:16:36Z nijel $ */
// vim: expandtab sw=4 ts=4 sts=4:

function PMA_transformation_text_plain__sql($buffer, $options = array(), $meta = '') {
    $result = PMA_SQP_formatHtml(PMA_SQP_parse($buffer));
    // Need to clear error state not to break subsequent queries display.
    PMA_SQP_resetError();
    return $result;
}

?>
