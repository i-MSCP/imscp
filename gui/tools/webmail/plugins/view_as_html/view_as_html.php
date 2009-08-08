<?php
    /*
     * $Id$
     */
    require_once(SM_PATH . 'functions/global.php');

    function view_as_html_set() {
        global $message, $show_html_default;
        sqgetGlobalVar('view_as_html',       $view_as_html);
        sqgetGlobalVar('view_unsafe_images', $view_unsafe_images);

        /*
         * We only worry if view_unsafe_images is passed from
         * a link.  Other plugins and options should handle it
         * the rest of the time.
         */
        if($view_unsafe_images == 1) {
            $view_as_html = 1;
        }
        if(isset($view_as_html)) {
            if ($view_as_html == 1) {
                $show_html_default = 1;
            } else if($view_as_html == 0) {
                $show_html_default = 0;
            }
        }
        /* Handle broken emails the have Content-Type w/o Mime-Version */
        if ($message->rfc822_header->content_type->type0 == 'text' &&
            $message->rfc822_header->content_type->type1 == 'html') {
            $message->header->type0 = 'text';
            $message->header->type1 = 'html';
            $message->type0 = 'text';
            $message->type1 = 'html';
        }
    }

    function view_as_html_link() {
        global $message, $show_html_default, $sort;
        $vars = array('passed_ent_id', 'show_more', 'override_type0', 'override_type1', 'where', 'what');

        sqgetGlobalVar('mailbox',      $mailbox);
        sqgetGlobalVar('passed_id',    $passed_id);
        sqgetGlobalVar('startMessage', $startMessage);
        sqgetGlobalVar('view_as_html', $view_as_html);

        $startMessage = (int)$startMessage;
        $passed_id    = (int)$passed_id;
        $view_as_html = (int)$view_as_html;

        $new_link = "read_body.php?passed_id=$passed_id&amp;startMessage=$startMessage" .
                    "&amp;mailbox=" .  urlencode($mailbox);

        foreach($vars as $var) {
            if(sqgetGlobalVar($var, $$var)) {
                $new_link .= '&amp;' . $var . '=' . urlencode($$var);
            }
        }
        $has_html = 0;

        if ($message->header->type0 == 'message' && $message->header->type1 == 'rfc822') {
            $type0 = $message->rfc822_header->content_type->type0;
            $type1 = $message->rfc822_header->content_type->type1;
        } else {
            $type0 = $message->header->type0;
            $type1 = $message->header->type1;
        }
        if($type0 == 'multipart' &&
           ($type1 == 'alternative' || $type1 == 'mixed' || $type1 == 'related')) {
            if ($message->findDisplayEntity(array(), array('text/html'), true)) {
                $has_html = 1;
            }
        }
        /*
         * Normal single part message so check its type.
         */
        else {
            if($type0 == 'text' && $type1 == 'html') {
                $has_html = 1;
            }
        }
        if ($has_html == 1) {
            include_once(SM_PATH . 'functions/i18n.php');
            echo '&nbsp;|&nbsp;';
            bindtextdomain('view_as_html', SM_PATH . 'plugins/view_as_html/locale');
            textdomain('view_as_html');
            if($show_html_default == 1) {
                echo "<a href=\"$new_link&amp;view_as_html=0\">";
                echo _("View as plain text");
            } else {
                echo "<a href=\"$new_link&amp;view_as_html=1\">";
                echo _("View as HTML");
            }
            echo "</a>\n";
            bindtextdomain('squirrelmail', SM_PATH . 'locale');
            textdomain('squirrelmail');
        }
    }
?>
