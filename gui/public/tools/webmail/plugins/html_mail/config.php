<?php

   global $editor_style, $customStyle, $allowEmoticons, $use_spell_checker, 
          $fully_loaded, $html_mail_aspell_path, $fck_spell_checker,
          $default_aggressive_html_reply, $allow_change_html_editor_style,
          $default_aggressive_reply_with_unsafe_images, 
          $outgoing_image_uri_https, $default_html_editor_height;


   // choose the editor you'd like to use.  currently, FCKeditor
   // and HTMLArea are supported
   //
   // 1 = FCKeditor
   // 2 = HTMLArea
   //
   $editor_style = 1;



   // allow user to choose their own editor style?
   //
   // 0 = no
   // 1 = yes
   //
   $allow_change_html_editor_style = 0;



   // default setting for "aggressive" replies in HTML format
   // (user can override in their personal settings)
   //
   // 0 = only reply in HTML if user had been
   //     viewing the message in HTML format
   // 1 = always try to reply in HTML format if 
   //     the message has an HTML part
   // 
   $default_aggressive_html_reply = 0;



   // default setting for "aggressive" inclusion of unsafe
   // images in replies in HTML format
   // (user can override in their personal settings)
   //
   // 0 = only include unsafe images in HTML reply if 
   //     user had been viewing the message in HTML 
   //     format with unsafe images enabled
   // 1 = always include any unsafe images in HTML replies
   // 
   $default_aggressive_reply_with_unsafe_images = 0;



   // outgoing mails may have embedded images (emoticons,
   // other images) that will be sent as URIs to your server
   // should those image URIs be sent as HTTP, HTTPS, or the
   // same as what the sender is using to log in with?
   //
   // 1 = HTTP
   // 2 = HTTPS
   //
   // Or set to the port number that HTTPS is served on on
   // your server to auto-sense and use whatever sender is 
   // using.  For example:
   //
   // $outgoing_image_uri_https = 443;
   //
   $outgoing_image_uri_https = 1;



   // this will set the editor window height default, which is 
   // usually only needed if you run a version of SquirrelMail
   // that is old enough not to have this setting in normal 
   // user preferences
   //
   $default_html_editor_height = '20';


   
   // ----------------------------------------------------------------
   //
   // FCKeditor configuration items
   //


   // choose if you want to use "ieSpell" or ASpell for you spellchecking
   // ieSpell does not require any server-side configuration, but only
   // runs in Microsoft Internet Explorer on Windows operating systems.
   // ASpell is a much better choice if you are able to install it on your
   // server
   //
   // 1 = ASpell
   // 2 = ieSpell
   //
   $fck_spell_checker = 1;



   // set the path to your ASpell binary.  Examples are included here
   // for both Windows and Linux users (only used when you have chosen 
   // ASpell as your spell checker).
   //
   // Linux:
   // $html_mail_aspell_path = 'aspell';
   //
   // Windows:
   // $html_mail_aspell_path = '"C:\Program Files\Aspell\bin\aspell.exe"';
   //
   $html_mail_aspell_path = 'aspell';



   // ----------------------------------------------------------------
   //
   // HTMLArea configuration items
   //


   // if you'd like to customize the HTML editor, you may define 
   // its CSS style here... leave as an empty string for default
   // behavior
   //
   // note that a font-size of about 16 seems to be more 
   // appropriate than the default size
   //
   //
//   $customStyle = '';
//   $customStyle = 'body { background-color: yellow; color: black; font-family: verdana,sans-serif; font-size:12 }   p { font-width: bold; }';
   $customStyle = 'body { font-size:16 }';



   // if you have patched the SquirrelMail source to allow the
   // use of emoticons functionality and want to allow users
   // to make use of them, set this value to 1.  set to zero
   // to disable
   //
   $allowEmoticons = 0;



   // set this to 1 if you'd like to use the integrated spell 
   // checker (make sure you've got your web server and Perl
   // interpreter set up correctly, for more info, see the 
   // README file)...  set to zero to disable
   //
   $use_spell_checker = 0;



   // set this to 1 if you want to turn on all bells and whistles,
   // including all HTMLArea plugins
   //
   $fully_loaded = 0;



?>
