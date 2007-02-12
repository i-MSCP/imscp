<?php

/**
  * SquirrelMail HTML Mail Plugin
  * Copyright (c) 2004-2005 Paul Lesneiwski <pdontthink@angrynerds.com>
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage html_mail
  *
  */



/**
  * Load configuration file
  *
  */
function hm_get_config()
{

   // Load sample config file, then allow overrides in
   // customized version thereof (order is important here!); 
   // error if neither is found
   //
/* Who wants to tell me why this returns TRUE when both files exist?!?
   if (!@include_once(SM_PATH . 'plugins/html_mail/config.php.sample')
    && !@include_once(SM_PATH . 'plugins/html_mail/config.php'))
*/
   $one = @include_once(SM_PATH . 'plugins/html_mail/config.php.sample');
   $two = @include_once(SM_PATH . 'plugins/html_mail/config.php');
   if (!$one && !$two)
      {
         echo sprintf(_("ERROR (%s): can't load config file"), 'html_mail');
         exit(1);
      }

}



/** 
  * Inserts controls on the compose page that let the user
  * switch between HTML and text on the fly.
  *
  */
function html_mail_choose_type_on_the_fly()
{

   bindtextdomain('html_mail', SM_PATH . 'locale');
   textdomain('html_mail');


   if (html_area_is_on_and_is_supported_by_users_browser())
   {
      echo '<input type="radio" name="strip_html_send_plain" id="strip_html_send_plain_html" CHECKED value="0" /><label for="strip_html_send_plain_html">' . _("HTML")
         . '</label><input type="radio" onClick="if (!confirm(\'' . _("Warning: all special formatting will be lost.  Are you sure you want to send your message in plain text format?") . '\')) document.compose.strip_html_send_plain[0].checked = true;" name="strip_html_send_plain" id="strip_html_send_plain_plain" value="1" /><label for="strip_html_send_plain_plain">' . _("Plain Text") . '</label>';

      global $comp_in_html;
      sqgetGlobalVar('comp_in_html', $comp_in_html, SQ_FORM);

      if ($comp_in_html)
         echo '<input type="hidden" name="comp_in_html" value="1" />';

   }


   else
   {
      global $javascript_on, $PHP_SELF;
      list($browser, $browserVersion) = getBrowserType();

      if ($javascript_on && (($browser == 'Explorer' && $browserVersion >= 5.5)
                          || ($browser == 'Gecko' && $browserVersion >= 20030624)))

//         echo '<a href="' . $PHP_SELF . (strpos($PHP_SELF, '?') === FALSE ? '?' : '&') . 'comp_in_html=1">'. _("Compose in HTML") . '</a>';
//         echo '<input type="button" onclick="window.location=\'' . $PHP_SELF . (strpos($PHP_SELF, '?') === FALSE ? '?' : '&') . 'comp_in_html=1\'" value="'. _("HTML") . '" />';
         echo '<input type="button" onclick="window.location=\'' . $PHP_SELF . (strpos($PHP_SELF, '?') === FALSE ? '?' : '&') . 'comp_in_html=1\'" value="'. _("Compose in HTML") . '" />';
   }



   bindtextdomain ('squirrelmail', SM_PATH . 'locale');
   textdomain ('squirrelmail');

}


/**
  * "Turns on" this plugin if the compose page is currently
  * being shown
  *
  */
function html_mail_header_do()
{

   global $PHP_SELF;

   if (stristr($PHP_SELF, 'compose.php'))
      html_mail_turn_on_htmlarea();

}


/** 
  * Do the actual insertion of the enhanced text editor
  *
  * Also check that this plugin is in the correct order in $plugins array
  *
  */
function html_mail_turn_on_htmlarea() 
{

   global $plugins, $color, $customStyle, $use_spell_checker, 
          $editor_style, $fully_loaded, $html_editor_style,
          $allow_change_html_editor_style, $username, $data_dir;


   // list of plugins that should come BEFORE this plugin (any that will
   // modify outgoing messages on the compose_send hook)
   //
   $check_for_previous_plugins = array(
      'gpg',    // is this one necessary?  oh well, no reason we can't play it safe
      'hancock', 
      'taglines',
      'quote_tools',
      'email_footer',
// nope... does not add after sending; adds using compose_form hook:      'sigtag',
   );


   // now just make sure html_mail comes after all those plugins listed above
   //
   $my_plugin_index = array_search('html_mail', $plugins);
   foreach ($check_for_previous_plugins as $plug)
   {
      $i = array_search($plug, $plugins);
      if (is_numeric($i) && $i > $my_plugin_index) // array_search returns NULL before PHP 4.2.0, FALSE after that
      {
         bindtextdomain('html_mail', SM_PATH . 'locale');
         textdomain('html_mail');

         echo "\n\n<html><body><h2><font color='red'>" 
            . sprintf(_("FATAL: HTML_Mail plugin must come AFTER %s in plugins array.  Please modify plugin order using conf.pl or by editing config/config.php"), $plug)
            . '</font></h2></body></html>';
         exit;
      }

   }


   hm_get_config();


   // turn it on if supported/turned on
   //
   if (html_area_is_on_and_is_supported_by_users_browser())
   {

      if ($allow_change_html_editor_style)
         $editor_style = getPref($data_dir, $username, 'html_editor_style', $editor_style);


      // FCKeditor
      //
      if ($editor_style == 1)
      {

         echo '<script type="text/javascript" src="' . SM_PATH . 'plugins/html_mail/fckeditor/fckeditor.js"></script>';

      }


   // HTMLArea editor
   //
   else if ($editor_style == 2)
   {

      global $squirrelmail_language;
      $htmlarea_language = substr($squirrelmail_language, 0, strpos($squirrelmail_language, '_'));
      if (!file_exists(SM_PATH . 'plugins/html_mail/htmlarea/lang/' . $htmlarea_language . '.js'))
         $htmlarea_language = 'en';

      echo '<script language="javascript" type="text/javascript">' 
         . "\n<!--\n"
         . 'var _editor_url = "' . SM_PATH . 'plugins/html_mail/htmlarea/";' . "\n"
         . 'var _editor_lang = "' . $htmlarea_language . '";' 
         . "\n// -->\n</script>\n"
         . '<script type="text/javascript" src="' . SM_PATH . 'plugins/html_mail/htmlarea/htmlarea.js"></script>'
         . '<script language="javascript" type="text/javascript">' 
         . "\n<!--\n";


      if ($use_spell_checker)
      {
         echo 'HTMLArea.loadPlugin("SpellChecker");';
      }

      if ($fully_loaded)
      {
         echo 'HTMLArea.loadPlugin("TableOperations");';
         echo 'HTMLArea.loadPlugin("FullPage");';
         echo 'HTMLArea.loadPlugin("CSS");';
         echo 'HTMLArea.loadPlugin("ContextMenu");';
         //echo 'HTMLArea.loadPlugin("HtmlTidy");';
         echo 'HTMLArea.loadPlugin("ListType");';
         echo 'HTMLArea.loadPlugin("CharacterMap");';
         echo 'HTMLArea.loadPlugin("DynamicCSS");';
      }


      ?>

         var editor = null;
         function initEditor() 
         {

            var config = new HTMLArea.Config();

            //=================================================
            // any other editor customizations here...
            //=================================================
            <?php 
               if (!empty($customStyle)) 
                  echo 'config.pageStyle = "' . $customStyle . '";';
            ?>



// NOTE: ideally, this requires change to src/compose.php --> textarea needs an "id" (this should be in SM 1.4.2)
//       attribute called "body", but this will work in most cases without it
            editor = new HTMLArea("body", config);
// without CSS:
//            editor = new HTMLArea("body");


      <?php if ($use_spell_checker) { ?>

            // register the SpellChecker plugin
            //
            editor.registerPlugin("SpellChecker");

      <?php } ?>

      <?php if ($fully_loaded) { ?>

            // register other plugins
            //
            editor.registerPlugin("TableOperations");
            editor.registerPlugin("FullPage");
            editor.registerPlugin("CSS", {
    combos : [
      { label: "Syntax:",
                   // menu text       // CSS class
        options: { "None"           : "",
                   "Code" : "code",
                   "String" : "string",
                   "Comment" : "comment",
                   "Variable name" : "variable-name",
                   "Type" : "type",
                   "Reference" : "reference",
                   "Preprocessor" : "preprocessor",
                   "Keyword" : "keyword",
                   "Function name" : "function-name",
                   "Html tag" : "html-tag",
                   "Html italic" : "html-helper-italic",
                   "Warning" : "warning",
                   "Html bold" : "html-helper-bold"
                 },
        context: "pre"
      },
      { label: "Info:",
        options: { "None"           : "",
                   "Quote"          : "quote",
                   "Highlight"      : "highlight",
                   "Deprecated"     : "deprecated"
                 }
      }
    ]
  });
            editor.config.pageStyle = "@import url(custom.css);";

            editor.registerPlugin("ContextMenu");
            //editor.registerPlugin("HtmlTidy");
            editor.registerPlugin("ListType");
            //editor.registerPlugin("CharacterMap");
            editor.registerPlugin("DynamicCSS");

      <?php } ?>

            editor.generate();
            return false;

         }

         HTMLArea.onload = initEditor;

         //-->
         </script>

      <?php


   }  // End Added For htmlarea

   }

}



/**
  * Inserts extra JavaScript at bottom of compose page
  * that is needed by the enhanced editor
  *
  */
function html_mail_footer() 
{

   global $editor_style, $html_editor_style, 
          $allow_change_html_editor_style, $username, $data_dir;
   hm_get_config();


   // insert javascript that actually replaces the text area, but only if supported/turned on
   //
   if (html_area_is_on_and_is_supported_by_users_browser())
   {

      // replace newlines with <br>'s in body
      // (comment out these three lines if you 
      // want to do this in html_mail_disable_squirrelspell_do()
      // and miss automated signatures)
      //
      echo '<script language="javascript" type="text/javascript">' . "\n<!--\n"
         . 'document.compose.body.value = document.compose.body.value.replace(/<br *\/?>(\r\n|\r|\n)/g, "\n");' . "\n"
         . 'document.compose.body.value = document.compose.body.value.replace(/<\/p>(\r\n|\r|\n)/g, "</p>");' . "\n"
         . 'document.compose.body.value = document.compose.body.value.replace(/\n/g, "<br />");'
         . "\n// -->\n</script>";


      if ($allow_change_html_editor_style)
         $editor_style = getPref($data_dir, $username, 'html_editor_style', $editor_style);


      // FCKeditor
      //
      if ($editor_style == 1)
      {

         global $squirrelmail_language, $editor_height, $editor_size, $default_html_editor_height;
         if (!$editor_height) $editor_height = $default_html_editor_height;
         $fckeditor_language = substr($squirrelmail_language, 0, strpos($squirrelmail_language, '_'));
         if (!file_exists(SM_PATH . 'plugins/html_mail/fckeditor/editor/lang/' . $fckeditor_language . '.js'))
            $fckeditor_language = 'en';

         echo '<script DEFER language="javascript" type="text/javascript">'
            . "\n<!--\n"
            . 'var oFCKeditor = new FCKeditor("body", ' 
            . ($editor_size * 7) . ', ' . ($editor_height * 16) . ');'
//            . 'var oFCKeditor = new FCKeditor("body");'
            . 'oFCKeditor.BasePath	= "' . SM_PATH . 'plugins/html_mail/fckeditor/";'
            . 'oFCKeditor.Config["AutoDetectLanguage"] = false;'
            . 'oFCKeditor.Config["DefaultLanguage"] = "' . $fckeditor_language . '";'
.'';
//            . 'oFCKeditor.Height = "' . ($editor_height * 16) . '";'
//            . 'oFCKeditor.Width = "' . ($editor_size * 7) . '";';


         // set right spell checker
         //
         global $fck_spell_checker;
         if ($fck_spell_checker == 1) 
            echo 'oFCKeditor.Config["SpellChecker"] = "SpellerPages";';
         else if ($fck_spell_checker == 2) 
            echo 'oFCKeditor.Config["SpellChecker"] = "ieSpell";';


         // attempt to focus correctly
         //
         global $reply_focus;
         if ($reply_focus == 'select') 
            echo 'oFCKeditor.Config["StartupFocus"] = "true";';
         else if ($reply_focus == 'focus') 
            echo 'oFCKeditor.Config["StartupFocus"] = "true";';
         else if ($reply_focus == 'none') 
            echo 'oFCKeditor.Config["StartupFocus"] = "false";';


         echo 'oFCKeditor.ReplaceTextarea();'
            . "\n// -->\n</script>";
   
      }


   // HTMLArea editor
   //
   else if ($editor_style == 2)
   {

      // actually replace the regular text area 
      //
      echo '<script DEFER language="javascript" type="text/javascript">HTMLArea.init();</script>' . "\n";
// the following replaces all textareas on the page w/out needing to know any IDs
//      echo '<script DEFER language="javascript" type="text/javascript">HTMLArea.replaceAll();</script>' . "\n";
// alternative way to replace just one known textarea, but our way is better cuz we get a 
// local variable with a reference to it
// i think this is wrong, isn't the function called replace() in this case?
//      echo '<script DEFER language="javascript" type="text/javascript">HTMLArea.replaceAll("body");</script>' . "\n";


/* wishful thinking.... doesnt' work:
      // attempt to focus correctly
      //
      global $reply_focus;
      echo '<script DEFER language="javascript" type="text/javascript">' 
         . "\n<!--\n";

      if ($reply_focus == 'select') 
         echo 'HTMLArea.focusEditor();';
         ///echo 'editor.focusEditor();';
      else if ($reply_focus == 'focus') 
         echo 'editor.focusEditor();';
         ///echo 'HTMLArea.focusEditor();';
         ///echo 'document.frames[0].document.forms["compose"].body.focus();';
      else if ($reply_focus == 'none')
         echo 'document.forms["compose"].send_to.focus();';

      echo "\n// -->\n</script>";
*/


   }

//DEBUGGING
//sm_print_r($_SERVER, $_POST, $_GET, $_FILES);
   }

}


/**
  * Enables display of emoticons on compose screen if possible
  *
  */
function html_mail_emoticons_do()
{

   global $username, $data_dir, $allowEmoticons, $use_emoticons, $editor_style,
          $html_editor_style, $allow_change_html_editor_style, $username, $data_dir;

   hm_get_config();


   if ($allow_change_html_editor_style)
      $editor_style = getPref($data_dir, $username, 'html_editor_style', $editor_style);


   if ($editor_style == 2 && $allowEmoticons && html_area_is_on_and_is_supported_by_users_browser())
   {

      $use_emoticons = getPref($data_dir, $username, 'compose_window_use_emoticons', '');

      if ($use_emoticons)
         insert_emoticons();

   }

}


/**
  * Turns off squirrelspell when the user is composing 
  * HTML-formatted email, since squirrelspell will
  * choke on the HTML.  This function also reformats the
  * message body as needed (such as getting the HTML 
  * part to edit if user settings demand it).
  *
  */
function html_mail_disable_squirrelspell_do()
{

   global $squirrelmail_plugin_hooks;

   if (html_area_is_on_and_is_supported_by_users_browser())
   {

      if (!empty($squirrelmail_plugin_hooks['compose_button_row']['squirrelspell']))
         unset($squirrelmail_plugin_hooks['compose_button_row']['squirrelspell']);


      // get global variables for versions of PHP < 4.1
      //
      if (!check_php_version(4, 1)) {
         global $HTTP_POST_FILES;
         $_FILES = $HTTP_POST_FILES;
      }


      // need to encode body text so > signs and other stuff don't 
      // get interpreted incorrectly as HTML entities
      //
      // but only need to do this once; don't repeat if user just
      // clicked to add a signature or upload a file or add addresses, etc
      //
      global $sigappend, $from_htmladdr_search, $restrict_senders_error_no_to_recipients,
             $restrict_senders_error_too_many_recipients;
      sqgetGlobalVar('sigappend', $sigappend, SQ_FORM);
      sqgetGlobalVar('from_htmladdr_search', $from_htmladdr_search, SQ_FORM);
      sqgetGlobalVar('restrict_senders_error_too_many_recipients', 
                     $restrict_senders_error_too_many_recipients, SQ_FORM);
      sqgetGlobalVar('restrict_senders_error_no_to_recipients', 
                     $restrict_senders_error_no_to_recipients, SQ_FORM);
      if ($sigappend != 'Signature'
       && $from_htmladdr_search != 'true'
       && $restrict_senders_error_no_to_recipients != 1
       && $restrict_senders_error_too_many_recipients != 1
       && empty($_FILES['attachfile']))
      {

         global $username, $key, $imapServerAddress, $imapPort, $imapConnection,
                $mailbox, $uid_support, $messages, $passed_id, $data_dir,
                $passed_ent_id, $smaction, $color, $wrap_at, $body;

         $aggressive_reply = getPref($data_dir, $username, 'html_mail_aggressive_reply', 0);
         $aggressive_reply_with_unsafe_images = getPref($data_dir, $username, 'html_mail_aggressive_reply_with_unsafe_images', 0);

         sqgetGlobalVar('messages',     $messages, SQ_SESSION);
         sqgetGlobalVar('smaction',     $smaction, SQ_FORM);
         sqgetGlobalVar('HTTP_REFERER', $referer,  SQ_SERVER);
         sqgetGlobalVar('key',          $key,      SQ_COOKIE);

         if ($smaction == 'reply' || $smaction == 'reply_all' || $smaction == 'forward'
          || $smaction == 'draft' || $smaction == 'edit_as_new')
         {

            // we can skip all this code that tries to get a HTML part
            // if user doesn't want it anyway
            //
            $treatAsPlainText = TRUE;
            if ($aggressive_reply 
             || (!empty($referer) && strpos($referer, 'view_as_html=1') !== FALSE))
            {

               $treatAsPlainText = FALSE;


               // prep IMAP connection
               //
               $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
               $mbx_response   = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);
               $uidvalidity    = $mbx_response['UIDVALIDITY'];

   
               if (!isset($messages[$uidvalidity])) 
                  $messages[$uidvalidity] = array();


               // grab message from session cache or from IMAP server
               //
               if (!isset($messages[$uidvalidity][$passed_id]) || !$uid_support) 
               {
                  $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
                  $message->is_seen = true;
                  $messages[$uidvalidity][$passed_id] = $message;
               } 
               else 
               {
                  $message = $messages[$uidvalidity][$passed_id];
               }
         

               // are we dealing with a message entity and not the message itself?
               //
               if (isset($passed_ent_id) && $passed_ent_id) 
               {
                  $message = $message->getEntity($passed_ent_id);
                  if ($message->type0 != 'message'  && $message->type1 != 'rfc822') 
                     $message = $message->parent;
   

                /*      we should already have this, but if people 
                        complain, uncommenting this may help...
                  $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[$passed_ent_id.HEADER]", true, $response, $msg, $uid_support);
                  $rfc822_header = new Rfc822Header();
                  $rfc822_header->parseHeader($read);
                  $message->rfc822_header = $rfc822_header;
                */


                  // I have no idea what I am doing, and I have had 
                  // enough digging in this code.  It makes a little
                  // sense that we might want to do this differently,
                  // but it seems to work anyway... (see below)
                  //
                  //$ent_ar = $message->entities[0]->findDisplayEntity(array(), array('text/html'), TRUE);

               } 
               else 
               {

                  // I have no idea what I am doing, and I have had 
                  // enough digging in this code.  It makes a little
                  // sense that we might want to do this differently,
                  // but it seems to work anyway... (see below)
                  //
                  //$ent_ar = $message->findDisplayEntity(array(), array('text/html'), TRUE);

               }
               $orig_header = $message->rfc822_header;


               // is there an html part?  if so, use it to redefine $body
               //
               $ent_ar = $message->findDisplayEntity(array(), array('text/html'), TRUE);
               if (!empty($ent_ar))
               {

                  //
                  // from compose.php (mutilated and modified...)
                  //

                  global $languages, $squirrelmail_language, $default_charset;
                  set_my_charset();

                  $unencoded_bodypart = mime_fetch_body($imapConnection, $passed_id, $ent_ar[0]);
                  $body_part_entity = $message->getEntity($ent_ar[0]);
                  $bodypart = decodeBody($unencoded_bodypart,
                                         $body_part_entity->header->encoding);

                  // handle HTML 
                  //
                  // do this after we call magicHTML()...   $bodypart = str_replace("\n", ' ', $bodypart);


                  // TODO: next line won't make a difference in ultimate result,
                  //       although we should keep an eye on it if problems arise,
                  $bodypart = str_replace(array('&nbsp;','&gt;','&lt;'),array(' ','>','<'),$bodypart);


                  // TODO: we can't strip out tags, cuz we want the tags!
                  //       but we don't want to be indescriminate, so we
                  //       use magicHTML() below... it is possible that
                  //       some people with extensive HTML mails will complain
                  //       about what magicHTML() does to their mail...
                  //$bodypart = strip_tags($bodypart);


                  // trick magicHTML() if needed by injecting info into $_GET
                  //
                  if ($aggressive_reply_with_unsafe_images 
                   || (!empty($referer) && strpos($referer, 'view_unsafe_images=1') !== FALSE))
                  {
                     global $_GET;
                     if (!check_php_version(4,1)) 
                     {
                        global $HTTP_GET_VARS;
                        $_GET = $HTTP_GET_VARS;
                     }
                     $_GET['view_unsafe_images'] = 1;
                  }


                  $bodypart = magicHTML($bodypart, $passed_id, $message, $mailbox, FALSE); // last param added in 1.5.1
                  $bodypart = str_replace("\n", ' ', $bodypart);


                  if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
                      function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) 
                  {
                     if (mb_detect_encoding($bodypart) != 'ASCII') 
                        $bodypart = $languages[$squirrelmail_language]['XTRA_CODE']('decode', $bodypart);
                  }
      
                  // charset encoding in compose form stuff
                  if (isset($body_part_entity->header->parameters['charset'])) 
                     $actual = $body_part_entity->header->parameters['charset'];
                  else
                     $actual = 'us-ascii';
         
                  if ( $actual && is_conversion_safe($actual) && $actual != $default_charset)
                     $bodypart = charset_convert($actual,$bodypart,$default_charset,false);
                  // end of charset encoding in compose

                  $body = $bodypart;


//----------------
// NOTE: I got this working pretty well on messages with just HTML-formatted text
//       where body quotes were inserted and lines were wrapped corectly (!)
//       But, it makes little sense to do this on anything more than that (<div>s,
//       <table>s, etc. make this a bad idea.  Comment this all out but leave here
//       for posterity...
/*
                  global $editor_size, $body_quote;
                  switch ($smaction) {
                     case ('draft'):
                        $body_ary = preg_split('/<br\s*\/?\s*>/', $body);
                        $cnt = count($body_ary) ;
                        $body = '';
                        for ($i=0; $i < $cnt; $i++) 
                        {
                           if (!ereg("^[>\\s]*$", $body_ary[$i])  || !$body_ary[$i]) 
                           {
                              sqHTMLWordWrap($body_ary[$i], $editor_size );
                              $body .= $body_ary[$i] . "\n";
                           }
                           unset($body_ary[$i]);
                        }
                        sqUnWordWrap($body);
                        break;
                     case ('edit_as_new'):
                        sqUnWordWrap($body);
                        break;
                     case ('forward'):
                        $body = getforwardHeader($orig_header) . $body;
                        sqUnWordWrap($body);
                        $body = '<br />' . $body;
                        break;
                     case ('reply_all'):
                     case ('reply'):
                        // this corrects some wrapping/quoting problems on replies 
                        $rewrap_body = preg_split('/<br\s*\/?\s*>/', $body);
                        $from =  (is_array($orig_header->from)) ? $orig_header->from[0] : $orig_header->from;
                        $body = '';
                        $cnt = count($rewrap_body);
                        for ($i=0;$i<$cnt;$i++)  
                        {
                           sqHTMLWordWrap($rewrap_body[$i], ($editor_size));
                           if (preg_match("/^(>+)/", $rewrap_body[$i], $matches)) 
                           {
                              $gt = $matches[1];
                              $body .= $body_quote . preg_replace('/<br\s*\/?\s*>/', '<br />' . $body_quote
                                    . "$gt ", rtrim($rewrap_body[$i])) . '<br />';
                           } else {
                              $body .= $body_quote . (!empty($body_quote) ? ' ' : '') . preg_replace('/<br\s*\/?\s*>/', '<br />' . $body_quote . (!empty($body_quote) ? ' ' : ''), rtrim($rewrap_body[$i])) . '<br />';
                           }
                           unset($rewrap_body[$i]);
                        }
// TODO: synch with SM when I make the advanced reply citation stuff 
//       Notes: The next line is mine where i was using $date for reply 
//              citation fix... below is Jonathan's.... need to merge the two
//                        $body = getReplyCitation($from, $date) . $body;
                        $body = getReplyCitation($from , $orig_header->date) . $body;

                        break;
                     default:
                        // we could give error here since this should never happen
                        break;
                  }
*/
//----------------
                  // NOTE: here is the alternative code we still need if we don't use the above
                  //
                  if ($smaction == 'forward')
                  {
                     $body = getforwardHeader($orig_header) . $body;
                     $body = '<br />' . $body;
                  }
                  if ($smaction == 'reply' || $smaction == 'reply_all')
                  {
                     $from =  (is_array($orig_header->from)) ? $orig_header->from[0] : $orig_header->from;
// TODO: synch with SM when I make the advanced reply citation stuff 
//       Notes: The next line is mine where i was using $date for reply 
//              citation fix... below is Jonathan's.... need to merge the two
//                     $body = getReplyCitation($from, $date) . $body;
                     $body = getReplyCitation($from , $orig_header->date) . $body;
                  }
//----------------
         
               }
               else $treatAsPlainText = TRUE;
   
            }
   

            // plain text messages: need to make sure HTML entities
            // don't get interpreted incorrectly...
            //
            if ($treatAsPlainText)
            {

               // email addresses in the form "name" <address> in 
               // the original message lose the address since it
               // is mistaken for a HTML tag
               //
               // ... sigh... have to encode twice...
               //
               // Update: WHY?  I can't reproduce the need for the second round
               // of encoding; I may have fixed that by adding the detection 
               // code for adding signatures, addresses, uploading files, etc. 
               // just above
               // 
               $body = htmlspecialchars($body);
               //$body = htmlspecialchars(htmlspecialchars($body));
   

               // this is faster than inserting line breaks using 
               // javascript (see html_mail_footer()) but it doesn't 
               // get the signature when "use signature" is turned
               // on, and the javascript method has been immensely
               // improved in HTML_Mail v2.1
               //
//               $body = nl2br($body);

            }
      
         }


         // NOTE: don't bind text domain to html_mail because the Subject
         //       and From translations below have to be from SM core

         // also, for some strange reason, the subject line
         // doesn't get a <br> before it in the forward header
         // unless there is a space after the newline.  
         // argh!
         //
         ///$body = str_replace("-----\n" . _("Subject"), "-----\n " . _("Subject"), $body);
         // 
         // in fact, it's not seen as a newline... why??
         //
         // update...  From: suffers from the same problem
         //
         $body = preg_replace(array('/-----\s' . _("Subject") . '/', '/\s' . _("From") . '/'), 
                              array("-----\n" . _("Subject"), "\n" . _("From")), $body);
   

      }

   }

}


function html_area_is_on_and_is_supported_by_users_browser()
{

   global $username, $data_dir, $javascript_on;

   global $comp_in_html;
   sqgetGlobalVar('comp_in_html', $comp_in_html, SQ_FORM);

   $type = getPref($data_dir, $username, 'compose_window_type', '');

   list($browser, $browserVersion) = getBrowserType();

   // NOTE: htmlarea 3 supports mozilla 1.4 and up... we'll 
   //       assume they meant gecko and not just mozilla
   //
   //       and also note that although "rv:1.4" is in the
   //       user agent string, the gecko engine 20030624
   //       should correspond to the correct version
   //
   return ($javascript_on && ($type == 'html' || $comp_in_html)
    && (($browser == 'Explorer' && $browserVersion >= 5.5)
     || ($browser == 'Gecko' && $browserVersion >= 20030624)
   ));

}


// this function can figure things out on its own
// or use the "Browser_Info" plugin if it is coded
// up to SM standards...
//
function getBrowserType()
{

   // get global variable for versions of PHP < 4.1
   //
   if (!check_php_version(4,1)) {
      global $HTTP_SERVER_VARS;
      $_SERVER = $HTTP_SERVER_VARS;
   }

   $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);


   if (preg_match('/opera[\s\/](\d+\.\d+)/', $userAgent, $version))
      $browser = 'Opera';
   else if (preg_match('/msie (\d+\.\d+)/', $userAgent, $version))
      $browser = 'Explorer';
   else if (preg_match('/gecko\/(\d+)/', $userAgent, $version))
      $browser = 'Gecko';

// Mozilla should be identified as Gecko above, and never get to this
// part... that's OK for our purposes, but if this causes problems,
// can push this else if above the Gecko lines above
   else if (preg_match('/mozilla\/(\d+\.\d+)/', $userAgent, $version))
      $browser = 'Mozilla';
   

//echo "$userAgent<hr><hr>$browser<br>" . $version[1];
   return array($browser, $version[1]);


// Example User Agent strings:
//
// MSIE 6 in Avant shell
// mozilla/4.0 (compatible; msie 6.0; windows nt 5.1; avant browser [avantbrowser.com]; .net clr 1.1.4322)
//
// Netscape 7
// mozilla/5.0 (windows; u; windows nt 5.1; en-us; rv:1.0.2) gecko/20030208 netscape/7.02
//
// Mozilla 1.1 (htmlarea doesn't work)
// mozilla/5.0 (windows; u; windows nt 5.1; en-us; rv:1.1) gecko/20020826
//
// Mozilla 1.4 (htmlarea does work!) 
// Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4) Gecko/20030624             (on linux)
// Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624     (on win2k)
// mozilla/5.0 (windows; u; windows nt 5.1; en-us; rv:1.4) gecko/20030624     (on winxp)
//
}


/**
  * Show user configuration items
  *
  */
function html_mail_display($hookName) 
{

   // 1.4.x - 1.5.0:  options go on display options page
   // 1.5.1  and up:  options go on compose options page
   //
   if (check_sm_version(1, 5, 1) && $hookName[0] != 'options_compose_inside')
      return;
   if (!check_sm_version(1, 5, 1) && $hookName[0] != 'options_display_inside')
      return;


   global $username, $data_dir, $email_type, $allowEmoticons, $use_emoticons,
          $editor_style, $html_mail_aggressive_reply, $default_aggressive_html_reply,
          $allow_change_html_editor_style, $html_mail_aggressive_reply_with_unsafe_images,
          $default_aggressive_reply_with_unsafe_images;

   hm_get_config();

   $email_type = getPref($data_dir, $username, 'compose_window_type', '');
   $html_mail_aggressive_reply = getPref($data_dir, $username, 'html_mail_aggressive_reply', $default_aggressive_html_reply);
   $html_mail_aggressive_reply_with_unsafe_images = getPref($data_dir, $username, 'html_mail_aggressive_reply_with_unsafe_images', $default_aggressive_reply_with_unsafe_images);
   $html_editor_style = getPref($data_dir, $username, 'html_editor_style', $editor_style);

   bindtextdomain('html_mail', SM_PATH . 'locale');
   textdomain('html_mail');



   // email_type
   //
   echo '<tr><td align=right valign="top"><br />'
      . _("Default Email Composition Format:") . "</td>\n"
      . '<td><br /><input type="radio" value="plain" name="email_type" id="compInPlain" ';

   if ($email_type == 'plain' || $email_type == '') echo 'CHECKED';

   echo '><label for="compInPlain">&nbsp;' . _("Plain Text") . "</label>\n"
      . '&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" value="html" id="compInHTML" name="email_type" ';

   if ($email_type == 'html') echo 'CHECKED';

   echo '><label for="compInHTML">&nbsp;' . _("HTML") . "</label>\n".
      '</td></tr>' . "\n";



   // use_emoticons
   //
   if ($allowEmoticons && $editor_style == 2)
   {

      $use_emoticons = getPref($data_dir, $username, 'compose_window_use_emoticons', '');

      echo '<tr><td align=right valign=top>'
         . _("Use Emoticons:") . "</td>\n"
         . '<td><input type="radio" value="1" name="use_emoticons" id="yesEmot" ';

      if ($use_emoticons) echo 'CHECKED';

      echo '><label for="yesEmot">&nbsp;' . _("Yes") . "</label>\n"
         . '&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" value="0" name="use_emoticons" id="noEmot" ';

      if (!$use_emoticons) echo 'CHECKED';

      echo '><label for="noEmot">&nbsp;' . _("No") . "</label>\n".
         '</td></tr>' . "\n";

   }



   // html_mail_aggressive_reply
   //
   echo '<tr><td align=right valign=top>'
      . _("Only Reply In HTML When Viewing HTML Format:") . "</td>\n"
      . '<td><table border="0"><tr><td>'
      . '<input type="radio" value="0" name="html_mail_aggressive_reply" id="aggressivehtmlreplyno" ';

   if (!$html_mail_aggressive_reply) echo 'CHECKED';

   echo '><label for="aggressivehtmlreplyno">&nbsp;' . _("Yes") . "</label>\n"
      . '</td><td><table border="0"><tr><td><input type="radio" value="1" id="aggressivehtmlreplyyes" name="html_mail_aggressive_reply" ';

   if ($html_mail_aggressive_reply) echo 'CHECKED';

   echo '></td><td><label for="aggressivehtmlreplyyes">&nbsp;' . _("No") . ' ' . _("(Always Attempt To Reply In HTML)") . "</label></td></tr></table></td></tr></table>\n".
      '</td></tr>' . "\n";



   // html_mail_aggressive_reply_with_unsafe_images
   //
   echo '<tr><td align=right valign=top>'
      . _("Only Allow Unsafe Images In HTML Replies When Viewing Unsafe Images:") . "</td>\n"
      . '<td><table border="0"><tr><td>'
      . '<input type="radio" value="0" name="html_mail_aggressive_reply_with_unsafe_images" id="aggressivereplyunsafeno" ';

   if (!$html_mail_aggressive_reply_with_unsafe_images) echo 'CHECKED';

   echo '><label for="aggressivereplyunsafeno">&nbsp;' . _("Yes") . "</label>\n"
      . '</td><td><table border="0"><tr><td><input type="radio" value="1" id="aggressivereplyunsafeyes" name="html_mail_aggressive_reply_with_unsafe_images" ';

   if ($html_mail_aggressive_reply_with_unsafe_images) echo 'CHECKED';

   echo '></td><td><label for="aggressivereplyunsafeyes">&nbsp;' . _("No") . ' ' . _("(Always Include Unsafe Images)") . "</label></td></tr></table></td></tr></table>\n".
      '</td></tr>' . "\n";



   // editor_style
   //
   if ($allow_change_html_editor_style)
   {
      echo '<tr><td align=right valign=top>'
         . _("HTML Editor Type:") . "</td>\n"
         . '<td>'
         . '<input type="radio" value="1" name="html_editor_style" id="htmleditor1" ';

      if ($html_editor_style == 1) echo 'CHECKED';

      echo '><label for="htmleditor1">&nbsp;' . _("FCKeditor") . "</label><br />\n"
         . '<input type="radio" value="2" name="html_editor_style" id="htmleditor2" ';

      if ($html_editor_style == 2) echo 'CHECKED';

      echo '><label for="htmleditor2">&nbsp;' . _("HTMLArea") . "</label><br />\n"
         . '</td></tr>' . "\n";
   }


   bindtextdomain ('squirrelmail', SM_PATH . 'locale');
   textdomain ('squirrelmail');

}



/**
  * Save user configuration items
  *
  */
function html_mail_save($hookName) 
{

   // 1.4.x - 1.5.0:  options go on display options page
   // 1.5.1  and up:  options go on compose options page
   //
   if (check_sm_version(1, 5, 1) && $hookName[0] != 'options_compose_save')
      return;
   if (!check_sm_version(1, 5, 1) && $hookName[0] != 'options_display_save')
      return;


   global $username, $data_dir, $email_type, $use_emoticons, $html_editor_style,
          $html_mail_aggressive_reply, $html_mail_aggressive_reply_with_unsafe_images,
          $allow_change_html_editor_style;

   hm_get_config();


   sqgetGlobalVar('email_type', $email_type, SQ_FORM);
   sqgetGlobalVar('use_emoticons', $use_emoticons, SQ_FORM);
   sqgetGlobalVar('html_mail_aggressive_reply', $html_mail_aggressive_reply, SQ_FORM);
   sqgetGlobalVar('html_mail_aggressive_reply_with_unsafe_images', 
                  $html_mail_aggressive_reply_with_unsafe_images, SQ_FORM);

   setPref($data_dir, $username, 'html_mail_aggressive_reply_with_unsafe_images', $html_mail_aggressive_reply_with_unsafe_images);
   setPref($data_dir, $username, 'html_mail_aggressive_reply', $html_mail_aggressive_reply);
   setPref($data_dir, $username, 'compose_window_type', $email_type);
   if (strlen($use_emoticons) > 0) 
      setPref($data_dir, $username, 'compose_window_use_emoticons', $use_emoticons);

   if ($allow_change_html_editor_style)
   {
      sqgetGlobalVar('html_editor_style', $html_editor_style, SQ_FORM);
      setPref($data_dir, $username, 'html_editor_style', $html_editor_style);
   }

}



/**
  * Changes outgoing message format to include multipart
  * html and text parts if needed
  *
  */
function html_mail_alter_type_do(&$argv)
{

   // change outgoing encoding if supported/turned on
   //
   if (html_area_is_on_and_is_supported_by_users_browser())
   {

      $message = &$argv[1];
//echo "<hr>";sm_print_r($message);echo "<hr>";exit;


      global $strip_html_send_plain, $base_uri;
      sqgetGlobalVar('strip_html_send_plain', $strip_html_send_plain, SQ_FORM);
      $serverAddress = get_location(); 
      if (strpos($serverAddress, '/') !== FALSE) 
         $serverAddress = substr($serverAddress, strpos($serverAddress, '/') + 2);
      if (strpos($serverAddress, '/') !== FALSE) 
         $serverAddress = substr($serverAddress, 0, strpos($serverAddress, '/'));


      // user wants to send this one in plain text,
      // so we have to:
      // 1) convert <p> and <br> into newlines
      // 2) strip the HTML out
      // 3) drop comments generated by html-stripping mechanism
      //
      if ($strip_html_send_plain)
      {

         if (is_array($message->entities) && sizeof($message->entities) > 0)
         {
            $msg = str_replace(array('<!-- begin sanitized html -->', '<!-- end sanitized html -->'), '', sq_sanitize( preg_replace('/(<br\s*\/?\s*>|<p\s*>)/i', "\n", $message->entities[0]->body_part), array(TRUE), array(), array(), array(), array(), array(), array(), array(), array(), array()));

            // need to decode special chars..
            //
            $message->entities[0]->body_part = my_html_entity_decode($msg);
         }
         else
         {
            $msg = str_replace(array('<!-- begin sanitized html -->', '<!-- end sanitized html -->'), '', sq_sanitize( preg_replace('/(<br\s*\/?\s*>|<p\s*>)/i', "\n", $message->body_part), array(TRUE), array(), array(), array(), array(), array(), array(), array(), array(), array()));

            // need to decode special chars..
            //
            $message->body_part = my_html_entity_decode($msg);
         }

      }


      // otherwise, set the outgoing content type correctly and add a 
      // text/plain mime part, which means non-multipart messages 
      // need to be converted to multipart...
      //
      else
      {

         // figure out how images should be linked (HTTP/HTTPS)
         //
         global $outgoing_image_uri_https;
         hm_get_config();
         if ($outgoing_image_uri_https == 1)
            $http = 'http';
         else if ($outgoing_image_uri_https == 2)
            $http = 'https';
         else 
         {
            // get global variable for versions of PHP < 4.1
            //
            if (!check_php_version(4,1)) {
               global $HTTP_SERVER_VARS;
               $_SERVER = $HTTP_SERVER_VARS;
            }
            if (isset($_SERVER['SERVER_PORT']))
               $serverPort = $_SERVER['SERVER_PORT'];
            else
               $serverPort = 0;
            $http = ($serverPort == $outgoing_image_uri_https ? 'https' : 'http');
         }


         // already multipart; change original message part to 
         // multipart/alternative and add a plain text and html
         // part therein 
         //
         if (is_array($message->entities) && sizeof($message->entities) > 0)
         {
            $plainText = str_replace(array('<!-- begin sanitized html -->', '<!-- end sanitized html -->'), '', sq_sanitize( preg_replace('/(<br\s*\/?\s*>|<p\s*>)/i', "\n", $message->entities[0]->body_part), array(TRUE), array(), array(), array(), array(), array(), array(), array(), array(), array()));
            $plainText = my_html_entity_decode($plainText);

            // convert relative URIs to absolute; also remove URIs
            // to download.php (embedded images, etc) until we 
            // find the time to code a way to forward on those images
            //
            $message->entities[0]->body_part 
              = preg_replace(array('|src=(["\'])' . $base_uri . '|si', 
                                   '|<img.*src=.*/src/download\.php.*?>|si'), 
                             array('src=\1' . $http . '://' . $serverAddress . $base_uri,
                                   '[IMAGE REMOVED]'), 
                             $message->entities[0]->body_part);

            $message->entities[0]->mime_header->type1 = 'html';
            $htmlTextPart = $message->entities[0];

            // break connection between $htmlTextPart and $message
            unset($message->entities[0]);
            // create new message part in place of removed one
            $message->entities[0] = new Message();
            $message->entities[0]->mime_header = new MessageHeader();

            // tag that message part as multipart alternative
            $message->entities[0]->mime_header->type0 = 'multipart';
            $message->entities[0]->mime_header->type1 = 'alternative';
            $message->entities[0]->mime_header->encoding = '';
            $message->entities[0]->mime_header->parameters = array();
            $message->entities[0]->body_part = '';

            // gets us a different message boundary 
            $message->entities[0]->entity_id = 'usf' . mt_rand(1000, 9999);

            // create new plaintext message
            $plainTextPart = new Message();
            $plainTextPart->body_part = $plainText;
            $mime_header = new MessageHeader;
            $mime_header->type0 = 'text';
            $mime_header->type1 = 'plain';
            $mime_header->encoding = $message->entities[0]->mime_header->encoding;
            $mime_header->parameters = $message->entities[0]->mime_header->parameters;
            $plainTextPart->mime_header = $mime_header;

            // add plain text and html entities to multipart/alternative message
            $message->entities[0]->addEntity($plainTextPart);
            $message->entities[0]->addEntity($htmlTextPart);

//echo "<hr>";sm_print_r($message);echo "<hr>";exit;
         }

         // not multipart; convert to multipart, change original message
         // to html and add text/plain part
         //
         else
         {
            $plainText = str_replace(array('<!-- begin sanitized html -->', '<!-- end sanitized html -->'), '', sq_sanitize( preg_replace('/(<br\s*\/?\s*>|<p\s*>)/i', "\n", $message->body_part), array(TRUE), array(), array(), array(), array(), array(), array(), array(), array(), array()));
            $plainText = my_html_entity_decode($plainText);

/* why is this here.... i hope it was just left over accidentally from development.  delete if no one complains after a while
            $message->body_part = preg_replace('|src="' . $base_uri . '|', 
                                               'src="' . $http . '://' . $serverAddress . $base_uri, 
                                               $message->body_part);
*/
            // convert relative URIs to absolute; also remove URIs
            // to download.php (embedded images, etc) until we 
            // find the time to code a way to forward on those images
            //
            $message->body_part 
              = preg_replace(array('|src=(["\'])' . $base_uri . '|si', 
                                   '|<img.*src=.*/src/download\.php.*?>|si'), 
                             array('src=\1' . $http . '://' . $serverAddress . $base_uri,
                                   '[IMAGE REMOVED]'), 
                             $message->body_part);

            $htmlTextPart = new Message();
            $htmlTextPart->body_part = $message->body_part;
            $htmlPartMime_header = new MessageHeader;
            $htmlPartMime_header->type0 = 'text';
            $htmlPartMime_header->type1 = 'html';
            $htmlPartMime_header->encoding = $message->rfc822_header->encoding;
            $htmlPartMime_header->parameters = $message->rfc822_header->content_type->properties;
            $htmlTextPart->mime_header = $htmlPartMime_header;

            $plainTextPart = new Message();
            $plainTextPart->body_part = $plainText;
            $plainPartMime_header = new MessageHeader;
            $plainPartMime_header->type0 = 'text';
            $plainPartMime_header->type1 = 'plain';
            $plainPartMime_header->encoding = $message->rfc822_header->encoding;
            $plainPartMime_header->parameters = $message->rfc822_header->content_type->properties;
            $plainTextPart->mime_header = $plainPartMime_header;

            // clear out some parts of the original non-multipart message
            //
            $message->rfc822_header->encoding = '';
            $message->rfc822_header->content_type->type0 = 'multipart';
            $message->rfc822_header->content_type->type1 = 'alternative';
            $message->rfc822_header->content_type->properties = array();
            $message->body_part = '';

            $message->entities = array($plainTextPart, $htmlTextPart);
//echo "<hr>";sm_print_r($message);echo "<hr>";exit;
 
         }
      }

      return $message;

   }

}


/**
  * Show emoticons on screen
  *
  */
function insert_emoticons()
{

   echo '<TR><TD colspan="2"><BR />';


   echo '

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/regular_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/regular_smile.gif" border="0" ALT="Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/teeth_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/teeth_smile.gif" border="0" ALT="Open-Mouthed Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/wink_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/wink_smile.gif" border="0" ALT="Winking Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/omg_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/omg_smile.gif" border="0" ALT="Surprised Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/tounge_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/tounge_smile.gif" border="0" ALT="Tounge-Out Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/shades_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/shades_smile.gif" border="0" ALT="Cool Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/angry_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/angry_smile.gif" border="0" ALT="Angry Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/confused_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/confused_smile.gif" border="0" ALT="Confused Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/embaressed_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/embaressed_smile.gif" border="0" ALT="Embarassed Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/sad_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/sad_smile.gif" border="0" ALT="Sad Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/cry_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/cry_smile.gif" border="0" ALT="Crying Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/whatchutalkingabout_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/whatchutalkingabout_smile.gif" border="0" ALT="Disappointed Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/angel_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/angel_smile.gif" border="0" ALT="Innocent Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/undecided.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/undecided.gif" border="0" ALT="Undecided Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/dude_hug.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/dude_hug.gif" border="0" ALT="Male Hug"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/girl_hug.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/girl_hug.gif" border="0" ALT="Female Hug"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/girl_handsacrossamerica.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/girl_handsacrossamerica.gif" border="0" ALT="Girl"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/guy_handsacrossamerica.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/guy_handsacrossamerica.gif" border="0" ALT="Boy"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/heart.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/heart.gif" border="0" ALT="Red Heart"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/broken_heart.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/broken_heart.gif" border="0" ALT="Broken Heart"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/rose.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/rose.gif" border="0" ALT="Red Rose"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/wilted_rose.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/wilted_rose.gif" border="0" ALT="Wilted Rose"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/kiss.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/kiss.gif" border="0" ALT="Kiss"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/beer_yum.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/beer_yum.gif" border="0" ALT="Beer"></a>

<BR>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/martini_shaken.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/martini_shaken.gif" border="0" ALT="Martini"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/coffee.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/coffee.gif" border="0" ALT="Coffee"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/bat.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/bat.gif" border="0" ALT="Bat"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/bowwow.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/bowwow.gif" border="0" ALT="Dog"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/kittykay.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/kittykay.gif" border="0" ALT="Cat"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/cake.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/cake.gif" border="0" ALT="Cake"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/present.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/present.gif" border="0" ALT="Gift"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/clock.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/clock.gif" border="0" ALT="Clock"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/devil_smile.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/devil_smile.gif" border="0" ALT="Devil Smiley"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/envelope.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/envelope.gif" border="0" ALT="Email"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/messenger.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/messenger.gif" border="0" ALT="MSN Messenger"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/phone.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/phone.gif" border="0" ALT="Phone Call"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/camera.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/camera.gif" border="0" ALT="Camera"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/film.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/film.gif" border="0" ALT="Movie"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/musical_note.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/musical_note.gif" border="0" ALT="Music"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/asl.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/asl.gif" border="0" ALT="Age/Sex/Location"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/handcuffs.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/handcuffs.gif" border="0" ALT="Handcuffs"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/sun.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/sun.gif" border="0" ALT="Sun"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/moon.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/moon.gif" border="0" ALT="Moon"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/lightbulb.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/lightbulb.gif" border="0" ALT="Light Bulb"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/star.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/star.gif" border="0" ALT="Star"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/thumbs_down.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/thumbs_down.gif" border="0" ALT="Thumbs Down"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/thumbs_up.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/thumbs_up.gif" border="0" ALT="Thumbs Up"></a>

<a href="javascript:editor.insertHTML(\'<img src=' . SM_PATH . 'plugins/html_mail/images/rainbow.gif>\');"><img src="' . SM_PATH . 'plugins/html_mail/images/rainbow.gif" border="0" ALT="Rainbow"></a>


   ';


   echo "</TD></TR>";

}



function my_html_entity_decode($text)
{

   if (function_exists('html_entity_decode'))
      return html_entity_decode($text);


   // copied from http://us3.php.net/preg-replace
   //
   $search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
                    "'<[\/\!]*?[^<>]*?>'si",           // Strip out html tags
                    "'([\r\n])[\s]+'",                 // Strip out white space
                    "'&(quot|#34);'i",                 // Replace html entities
                    "'&(amp|#38);'i",
                    "'&(lt|#60);'i",
                    "'&(gt|#62);'i",
                    "'&(nbsp|#160);'i",
                    "'&(iexcl|#161);'i",
                    "'&(cent|#162);'i",
                    "'&(pound|#163);'i",
                    "'&(copy|#169);'i",
                    "'&#(\d+);'e");                    // evaluate as php

   $replace = array ("",
                     "",
                     "\\1",
                     "\"",
                     "&",
                     "<",
                     ">",
                     " ",
                     chr(161),
                     chr(162),
                     chr(163),
                     chr(169),
                     "chr(\\1)");

   return preg_replace ($search, $replace, $text);

}



/**
 * Wraps text at $wrap characters while preserving HTML tags
 *
 * Has a problem with special HTML characters, so call this before
 * you do character translation.
 *
 * Specifically, &#039 comes up as 5 characters instead of 1.
 * This should not add newlines to the end of lines.
 */
function sqHTMLWordWrap(&$line, $wrap) {
    global $languages, $squirrelmail_language;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        if (mb_detect_encoding($line) != 'ASCII') {
            $line = $languages[$squirrelmail_language]['XTRA_CODE']('wordwrap', $line, $wrap);
            return;
        }
    }

    ereg("^([\t >]*)([^\t >].*)?$", $line, $regs);
    $beginning_spaces = $regs[1];
    if (isset($regs[2])) {
        $words = explode(' ', $regs[2]);
    } else {
        $words = '';
    }

    // pull words back together if they have split up a tag
    //
    $newWords = array();
    $newWord = '';
    foreach ($words as $word)
    {
       $newWord .= ' ' . $word;
       
       // this is a bit simplistic; a message might have 
       // a less than sign without it being an opening
       // tag marker, but we'll go with this since grepping
       // for all possible tags is much more trouble and 
       // there are probably not many messages where this 
       // will be a problem
       //
       // this won't work:  if tag doesn't end in next word segment...
       //if (strpos($word, '<') === FALSE)
       //
       $LTcount = preg_match_all('/</', $newWord, $junk);
       $GTcount = preg_match_all('/>/', $newWord, $junk);
       if ($LTcount == $GTcount)
       {
          $newWords[] = $newWord;
          $newWord = '';
       }
       
    }
    $words = $newWords;

    $i = 0;
    $line = $beginning_spaces;

    while ($i < count($words)) {
        /* Force one word to be on a line (minimum) */
        $line .= $words[$i];
        $line_len = strlen($beginning_spaces) + strlen(strip_tags($words[$i])) + 2;
        if (isset($words[$i + 1]))
            $line_len += strlen(strip_tags($words[$i + 1]));
        $i ++;

        /* Add more words (as long as they fit) */
        while ($line_len < $wrap && $i < count($words)) {
            $line .= ' ' . $words[$i];
            $i++;
            if (isset($words[$i]))
                $line_len += strlen(strip_tags($words[$i])) + 1;
            else
                $line_len += 1;
        }

        /* Skip spaces if they are the first thing on a continued line */
        while (!isset($words[$i]) && $i < count($words)) {
            $i ++;
        }

        /* Go to the next line if we have more to process */
        if ($i < count($words)) {
            $line .= "<br />";
        }
    }
}



?>
