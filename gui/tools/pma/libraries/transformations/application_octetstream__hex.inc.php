<?php
/* $Id: application_octetstream__hex.inc.php,v 1.1 2005/06/24 14:28:00 nijel Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:

function PMA_transformation_application_octetstream__hex($buffer, $options = array(), $meta = '') {
    // possibly use a global transform and feed it with special options:
    // include('./libraries/transformations/global.inc.php');

    return chunk_split(bin2hex($buffer), 2, ' ');
}

?>
