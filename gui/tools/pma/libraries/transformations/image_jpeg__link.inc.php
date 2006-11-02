<?php
/* $Id: image_jpeg__link.inc.php 5215 2003-12-02 15:49:21Z lem9 $ */
// vim: expandtab sw=4 ts=4 sts=4:

function PMA_transformation_image_jpeg__link($buffer, $options = array(), $meta = '') {
    require_once('./libraries/transformations/global.inc.php');

    $transform_options = array ('string' => '<a href="transformation_wrapper.php' . $options['wrapper_link'] . '" alt="[__BUFFER__]">[BLOB]</a>');
    $buffer = PMA_transformation_global_html_replace($buffer, $transform_options);

    return $buffer;
}

?>
