<?php

/**
 * Deliver.class.php
 *
 * This contains all the functions needed to send messages through
 * a delivery backend.
 *
 * @author Marc Groot Koerkamp
 * @copyright &copy; 1999-2009 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: Deliver.class.php 13549 2009-04-15 22:00:49Z jervfors $
 * @package squirrelmail
 */

/**
 * Deliver Class - called to actually deliver the message
 *
 * This class is called by compose.php and other code that needs
 * to send messages.  All delivery functionality should be centralized
 * in this class.
 *
 * Do not place UI code in this class, as UI code should be placed in templates
 * going forward.
 *
 * @author  Marc Groot Koerkamp
 * @package squirrelmail
 */
class Deliver {

    /**
     * function mail - send the message parts to the SMTP stream
     *
     * @param Message  $message      Message object to send
     *                               NOTE that this is passed by
     *                               reference and will be modified
     *                               upon return with updated
     *                               fields such as Message ID, References,
     *                               In-Reply-To and Date headers.
     * @param resource $stream       Handle to the outgoing stream
     *                               (when FALSE, nothing will be
     *                               written to the stream; this can
     *                               be used to determine the actual
     *                               number of bytes that will be 
     *                               written to the stream)
     * @param string   $reply_id     Identifies message being replied to
     *                               (OPTIONAL; caller should ONLY specify
     *                               a value for this when the message
     *                               being sent is a reply)
     * @param string   $reply_ent_id Identifies message being replied to
     *                               in the case it was an embedded/attached
     *                               message inside another (OPTIONAL; caller
     *                               should ONLY specify a value for this 
     *                               when the message being sent is a reply)
     * @param resource $imap_stream  If there is an open IMAP stream in 
     *                               the caller's context, it should be
     *                               passed in here.  This is OPTIONAL,
     *                               as one will be created if not given,
     *                               but as some IMAP servers may baulk
     *                               at opening more than one connection
     *                               at a time, the caller should always
     *                               abide if possible.  Currently, this
     *                               stream is only used when $reply_id
     *                               is also non-zero, but that is subject
     *                               to change.
     * @param mixed    $extra        Any implementation-specific variables
     *                               can be passed in here and used in
     *                               an overloaded version of this method
     *                               if needed.
     *
     * @return integer The number of bytes written (or that would have been
     *                 written) to the output stream.
     *
     */
    function mail(&$message, $stream=false, $reply_id=0, $reply_ent_id=0, 
                  $imap_stream=NULL, $extra=NULL) {

        $rfc822_header = &$message->rfc822_header;

        if (count($message->entities)) {
            $boundary = $this->mimeBoundary();
            $rfc822_header->content_type->properties['boundary']='"'.$boundary.'"';
        } else {
            $boundary='';
        }
        $raw_length = 0;


        // calculate reply header if needed
        //
        if ($reply_id) {
            global $imapConnection, $username, $key, $imapServerAddress, 
                   $imapPort, $mailbox;

            // try our best to use an existing IMAP handle
            //
            $close_imap_stream = FALSE;
            if (is_resource($imap_stream)) {
                $my_imap_stream = $imap_stream;

            } else if (is_resource($imapConnection)) {
                $my_imap_stream = $imapConnection;

            } else {
                $close_imap_stream = TRUE;
                $my_imap_stream = sqimap_login($username, $key,
                                               $imapServerAddress, $imapPort, 0);
            } 

            sqimap_mailbox_select($my_imap_stream, $mailbox);
            $reply_message = sqimap_get_message($my_imap_stream, $reply_id, $mailbox);

            if ($close_imap_stream) {
                sqimap_logout($my_imap_stream);
            }

            if ($reply_ent_id) {
                /* redefine the messsage in case of message/rfc822 */
                $reply_message = $message->getEntity($reply_ent_id);
                /* message is an entity which contains the envelope and type0=message
                 * and type1=rfc822. The actual entities are childs from
                 * $reply_message->entities[0]. That's where the encoding and is located
                 */

                $orig_header = $reply_message->rfc822_header; /* here is the envelope located */

            } else {
                $orig_header = $reply_message->rfc822_header;
            }
            $message->reply_rfc822_header = $orig_header;            
        }


        $reply_rfc822_header = (isset($message->reply_rfc822_header)
                             ? $message->reply_rfc822_header : '');
        $header = $this->prepareRFC822_Header($rfc822_header, $reply_rfc822_header, $raw_length);

        $this->send_mail($message, $header, $boundary, $stream, $raw_length, $extra);

        return $raw_length;
    }

    /**
     * function send_mail - send the message parts to the IMAP stream
     *
     * @param Message  $message      Message object to send
     * @param string   $header       Headers ready to send
     * @param string   $boundary     Message parts boundary
     * @param resource $stream       Handle to the SMTP stream
     *                               (when FALSE, nothing will be
     *                               written to the stream; this can
     *                               be used to determine the actual
     *                               number of bytes that will be
     *                               written to the stream)
     * @param int     &$raw_length   The number of bytes written (or that
     *                               would have been written) to the 
     *                               output stream - NOTE that this is
     *                               passed by reference
     * @param mixed    $extra        Any implementation-specific variables
     *                               can be passed in here and used in
     *                               an overloaded version of this method
     *                               if needed.
     *
     * @return void
     *
     */
    function send_mail($message, $header, $boundary, $stream=false, 
                       &$raw_length, $extra=NULL) {

        if ($stream) {
            $this->preWriteToStream($header);
            $this->writeToStream($stream, $header);
        }
        $this->writeBody($message, $stream, $raw_length, $boundary);
    }

    /**
     * function writeBody - generate and write the mime boundaries around each part to the stream
     *
     * Recursively formats and writes the MIME boundaries of the $message
     * to the output stream.
     *
     * @param Message   $message      Message object to transform
     * @param resource  $stream       SMTP output stream
     *                                (when FALSE, nothing will be
     *                                written to the stream; this can
     *                                be used to determine the actual
     *                                number of bytes that will be 
     *                                written to the stream)
     * @param integer  &$length_raw   raw length of the message (part)
     *                                as returned by mail fn
     * @param string    $boundary     custom boundary to call, usually for subparts
     *
     * @return void
     */
    function writeBody($message, $stream, &$length_raw, $boundary='') {
        // calculate boundary in case of multidimensional mime structures
        if ($boundary && $message->entity_id && count($message->entities)) {
            if (strpos($boundary,'_part_')) {
                $boundary = substr($boundary,0,strpos($boundary,'_part_'));

            // the next four lines use strrev to reverse any nested boundaries
            // because RFC 2046 (5.1.1) says that if a line starts with the outer
            // boundary string (doesn't matter what the line ends with), that
            // can be considered a match for the outer boundary; thus the nested
            // boundary needs to be unique from the outer one
            //
            } else if (strpos($boundary,'_trap_')) {
                $boundary = substr(strrev($boundary),0,strpos(strrev($boundary),'_part_'));
            }
            $boundary_new = strrev($boundary . '_part_'.$message->entity_id);
        } else {
            $boundary_new = $boundary;
        }
        if ($boundary && !$message->rfc822_header) {
            $s = '--'.$boundary."\r\n";
            $s .= $this->prepareMIME_Header($message, $boundary_new);
            $length_raw += strlen($s);
            if ($stream) {
                $this->preWriteToStream($s);
                $this->writeToStream($stream, $s);
            }
        }
        $this->writeBodyPart($message, $stream, $length_raw);

        $last = false;
        for ($i=0, $entCount=count($message->entities);$i<$entCount;$i++) {
            $msg = $this->writeBody($message->entities[$i], $stream, $length_raw, $boundary_new);
            if ($i == $entCount-1) $last = true;
        }
        if ($boundary && $last) {
            $s = "--".$boundary_new."--\r\n\r\n";
            $length_raw += strlen($s);
            if ($stream) {
                $this->preWriteToStream($s);
                $this->writeToStream($stream, $s);
            }
        }
    }

    /**
     * function writeBodyPart - write each individual mimepart
     *
     * Recursively called by WriteBody to write each mime part to the SMTP stream
     *
     * @param Message   $message      Message object to transform
     * @param resource  $stream       SMTP output stream
     *                                (when FALSE, nothing will be
     *                                written to the stream; this can
     *                                be used to determine the actual
     *                                number of bytes that will be 
     *                                written to the stream)
     * @param integer  &$length       length of the message part
     *                                as returned by mail fn
     *
     * @return void
     */
    function writeBodyPart($message, $stream, &$length) {
        if ($message->mime_header) {
            $type0 = $message->mime_header->type0;
        } else {
            $type0 = $message->rfc822_header->content_type->type0;
        }

        $body_part_trailing = $last = '';
        switch ($type0)
        {
        case 'text':
        case 'message':
            if ($message->body_part) {
                $body_part = $message->body_part;
                // remove NUL characters
                $body_part = str_replace("\0",'',$body_part);
                $length += $this->clean_crlf($body_part);
                if ($stream) {
                    $this->preWriteToStream($body_part);
                    $this->writeToStream($stream, $body_part);
                }
                $last = $body_part;
            } elseif ($message->att_local_name) {
                global $username, $attachment_dir;
                $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
                $filename = $message->att_local_name;

                // inspect attached file for lines longer than allowed by RFC,
                // in which case we'll be using base64 encoding (so we can split
                // the lines up without corrupting them) instead of 8bit unencoded...
                // (see RFC 2822/2.1.1)
                //
                // using 990 because someone somewhere is folding lines at
                // 990 instead of 998 and I'm too lazy to find who it is
                //
                $file_has_long_lines = file_has_long_lines($hashed_attachment_dir
                                                           . '/' . $filename, 990);

                $file = fopen ($hashed_attachment_dir . '/' . $filename, 'rb');

                // long lines were found, need to use base64 encoding
                //
                if ($file_has_long_lines) {
                    while ($tmp = fread($file, 570)) {
                        $body_part = chunk_split(base64_encode($tmp));
                        // Up to 4.3.10 chunk_split always appends a newline,
                        // while in 4.3.11 it doesn't if the string to split
                        // is shorter than the chunk length.
                        if( substr($body_part, -1 , 1 ) != "\n" )
                            $body_part .= "\n";
                        $length += $this->clean_crlf($body_part);
                        if ($stream) {
                            $this->writeToStream($stream, $body_part);
                        }
                    }
                }

                // no excessively long lines - normal 8bit
                //
                else {
                    while ($body_part = fgets($file, 4096)) {
                        $length += $this->clean_crlf($body_part);
                        if ($stream) {
                            $this->preWriteToStream($body_part);
                            $this->writeToStream($stream, $body_part);
                        }
                        $last = $body_part;
                    }
                }

                fclose($file);
            }
            break;
        default:
            if ($message->body_part) {
                $body_part = $message->body_part;
                $length += $this->clean_crlf($body_part);
                if ($stream) {
                    $this->writeToStream($stream, $body_part);
                }
            } elseif ($message->att_local_name) {
                global $username, $attachment_dir;
                $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
                $filename = $message->att_local_name;
                $file = fopen ($hashed_attachment_dir . '/' . $filename, 'rb');
                
                while ($tmp = fread($file, 570)) {
                    $body_part = chunk_split(base64_encode($tmp));
                    // Up to 4.3.10 chunk_split always appends a newline,
                    // while in 4.3.11 it doesn't if the string to split
                    // is shorter than the chunk length.
                    if( substr($body_part, -1 , 1 ) != "\n" )
                        $body_part .= "\n";
                    $length += $this->clean_crlf($body_part);
                    if ($stream) {
                        $this->writeToStream($stream, $body_part);
                    }
                }
                fclose($file);
            }
            break;
        }
        $body_part_trailing = '';
        if ($last && substr($last,-1) != "\n") {
            $body_part_trailing = "\r\n";
        }
        if ($body_part_trailing) {
            $length += strlen($body_part_trailing);
            if ($stream) {
                $this->preWriteToStream($body_part_trailing);
                $this->writeToStream($stream, $body_part_trailing);
            }
        }
    }

    /**
     * function clean_crlf - change linefeeds and newlines to legal characters
     *
     * The SMTP format only allows CRLF as line terminators.
     * This function replaces illegal teminators with the correct terminator.
     *
     * @param string &$s string to clean linefeeds on
     *
     * @return void
     */
    function clean_crlf(&$s) {
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        $s = str_replace("\n", "\r\n", $s);
        return strlen($s);
    }

    /**
     * function strip_crlf - strip linefeeds and newlines from a string
     *
     * The SMTP format only allows CRLF as line terminators.
     * This function strips all line terminators from the string.
     *
     * @param string &$s string to clean linefeeds on
     *
     * @return void
     */
    function strip_crlf(&$s) {
        $s = str_replace("\r\n ", '', $s);
        $s = str_replace("\r", '', $s);
        $s = str_replace("\n", '', $s);
    }

    /**
     * function preWriteToStream - reserved for extended functionality
     *
     * This function is not yet implemented.
     * Reserved for extended functionality.
     *
     * @param string &$s string to operate on
     *
     * @return void
     */
    function preWriteToStream(&$s) {
    }

    /**
     * function writeToStream - write data to the SMTP stream
     *
     * @param resource $stream  SMTP output stream
     * @param string   $data    string with data to send to the SMTP stream
     *
     * @return void
     */
    function writeToStream($stream, $data) {
        fputs($stream, $data);
    }

    /**
     * function initStream - reserved for extended functionality
     *
     * This function is not yet implemented.
     * Reserved for extended functionality.
     *
     * @param Message $message  Message object
     * @param string  $host     host name or IP to connect to
     * @param string  $user     username to log into the SMTP server with
     * @param string  $pass     password to log into the SMTP server with
     * @param integer $length
     *
     * @return handle $stream file handle resource to SMTP stream
     */
    function initStream($message, $length=0, $host='', $port='', $user='', $pass='') {
        return $stream;
    }

    /**
     * function getBCC - reserved for extended functionality
     *
     * This function is not yet implemented.
     * Reserved for extended functionality.
     *
     */
    function getBCC() {
        return false;
    }

    /**
     * function prepareMIME_Header - creates the mime header
     *
     * @param Message $message  Message object to act on
     * @param string  $boundary mime boundary from fn MimeBoundary
     *
     * @return string $header properly formatted mime header
     */
    function prepareMIME_Header($message, $boundary) {
        $mime_header = $message->mime_header;
        $rn="\r\n";
        $header = array();

        $contenttype = 'Content-Type: '. $mime_header->type0 .'/'.
                        $mime_header->type1;
        if (count($message->entities)) {
            $contenttype .= ';' . 'boundary="'.$boundary.'"';
        }
        if (isset($mime_header->parameters['name'])) {
            $contenttype .= '; name="'.
            encodeHeader($mime_header->parameters['name']). '"';
        }
        if (isset($mime_header->parameters['charset'])) {
            $charset = $mime_header->parameters['charset'];
            $contenttype .= '; charset="'.
            encodeHeader($charset). '"';
        }

        $header[] = $contenttype . $rn;
        if ($mime_header->description) {
            $header[] = 'Content-Description: ' . $mime_header->description . $rn;
        }
        if ($mime_header->encoding) {
            $encoding = $mime_header->encoding;
            $header[] = 'Content-Transfer-Encoding: ' . $mime_header->encoding . $rn;
        } else {

            // inspect attached file for lines longer than allowed by RFC,
            // in which case we'll be using base64 encoding (so we can split
            // the lines up without corrupting them) instead of 8bit unencoded...
            // (see RFC 2822/2.1.1)
            //
            if (!empty($message->att_local_name)) { // is this redundant? I have no idea
                global $username, $attachment_dir;
                $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
                $filename = $hashed_attachment_dir . '/' . $message->att_local_name;

                // using 990 because someone somewhere is folding lines at
                // 990 instead of 998 and I'm too lazy to find who it is
                //
                $file_has_long_lines = file_has_long_lines($filename, 990);
            } else
                $file_has_long_lines = FALSE;

            if ($mime_header->type0 == 'multipart' || $mime_header->type0 == 'alternative') {
                /* no-op; no encoding needed */
            } else if (($mime_header->type0 == 'text' || $mime_header->type0 == 'message')
                    && !$file_has_long_lines) {
                $header[] = 'Content-Transfer-Encoding: 8bit' .  $rn;
            } else {
                $header[] = 'Content-Transfer-Encoding: base64' .  $rn;
            }
        }
        if ($mime_header->id) {
            $header[] = 'Content-ID: ' . $mime_header->id . $rn;
        }
        if ($mime_header->disposition) {
            $disposition = $mime_header->disposition;
            $contentdisp = 'Content-Disposition: ' . $disposition->name;
            if ($disposition->getProperty('filename')) {
                $contentdisp .= '; filename="'.
                encodeHeader($disposition->getProperty('filename')). '"';
            }
            $header[] = $contentdisp . $rn;
        }
        if ($mime_header->md5) {
            $header[] = 'Content-MD5: ' . $mime_header->md5 . $rn;
        }
        if ($mime_header->language) {
            $header[] = 'Content-Language: ' . $mime_header->language . $rn;
        }

        $cnt = count($header);
        $hdr_s = '';
        for ($i = 0 ; $i < $cnt ; $i++)    {
            $hdr_s .= $this->foldLine($header[$i], 78);
        }
        $header = $hdr_s;
        $header .= $rn; /* One blank line to separate mimeheader and body-entity */
        return $header;
    }

    /**
     * function prepareRFC822_Header - prepares the RFC822 header string from Rfc822Header object(s)
     *
     * This function takes the Rfc822Header object(s) and formats them
     * into the RFC822Header string to send to the SMTP server as part
     * of the SMTP message.
     *
     * @param Rfc822Header  $rfc822_header
     * @param Rfc822Header  $reply_rfc822_header
     * @param integer      &$raw_length length of the message
     *
     * @return string $header
     */
    function prepareRFC822_Header(&$rfc822_header, $reply_rfc822_header, &$raw_length) {
        global $domain, $version, $username, $encode_header_key, $edit_identity, $hide_auth_header;

        if (! isset($hide_auth_header)) $hide_auth_header=false;

        /* if server var SERVER_NAME not available, use $domain */
        if(!sqGetGlobalVar('SERVER_NAME', $SERVER_NAME, SQ_SERVER)) {
            $SERVER_NAME = $domain;
        }

        sqGetGlobalVar('REMOTE_ADDR', $REMOTE_ADDR, SQ_SERVER);
        sqGetGlobalVar('REMOTE_PORT', $REMOTE_PORT, SQ_SERVER);
        sqGetGlobalVar('REMOTE_HOST', $REMOTE_HOST, SQ_SERVER);
        sqGetGlobalVar('HTTP_VIA',    $HTTP_VIA,    SQ_SERVER);
        sqGetGlobalVar('HTTP_X_FORWARDED_FOR', $HTTP_X_FORWARDED_FOR, SQ_SERVER);

        $rn = "\r\n";

        /* This creates an RFC 822 date */
        $date = date('D, j M Y H:i:s ', time()) . $this->timezone();

        /* Create a message-id */
        $message_id = 'MESSAGE ID GENERATION ERROR! PLEASE CONTACT SQUIRRELMAIL DEVELOPERS';
        if (empty($rfc822_header->message_id)) {
            $message_id = '<';
            /* user-specifc data to decrease collision chance */
            $seed_data = $username . '.';
            $seed_data .= (!empty($REMOTE_PORT) ? $REMOTE_PORT . '.' : '');
            $seed_data .= (!empty($REMOTE_ADDR) ? $REMOTE_ADDR . '.' : '');
            /* add the current time in milliseconds and randomness */
            $seed_data .= uniqid(mt_rand(),true);
            /* put it through one-way hash and add it to the ID */
            $message_id .= md5($seed_data) . '.squirrel@' . $SERVER_NAME .'>';
        }

        /* Make an RFC822 Received: line */
        if (isset($REMOTE_HOST)) {
            $received_from = "$REMOTE_HOST ([$REMOTE_ADDR])";
        } else {
            $received_from = $REMOTE_ADDR;
        }
        if (isset($HTTP_VIA) || isset ($HTTP_X_FORWARDED_FOR)) {
            if (!isset($HTTP_X_FORWARDED_FOR) || $HTTP_X_FORWARDED_FOR == '') {
                $HTTP_X_FORWARDED_FOR = 'unknown';
            }
            $received_from .= " (proxying for $HTTP_X_FORWARDED_FOR)";
        }
        $header = array();

        /**
         * SquirrelMail header
         *
         * This Received: header provides information that allows to track
         * user and machine that was used to send email. Don't remove it
         * unless you understand all possible forging issues or your
         * webmail installation does not prevent changes in user's email address.
         * See SquirrelMail bug tracker #847107 for more details about it.
         */
        // FIXME: The following headers may generate slightly differently between the message sent to the destination and that stored in the Sent folder because this code will be called before both actions.  This is not necessarily a big problem, but other headers such as Message-ID and Date are preserved between both actions
        if (isset($encode_header_key) &&
            trim($encode_header_key)!='') {
            // use encoded headers, if encryption key is set and not empty
            $header[] = 'X-Squirrel-UserHash: '.OneTimePadEncrypt($username,base64_encode($encode_header_key)).$rn;
            $header[] = 'X-Squirrel-FromHash: '.OneTimePadEncrypt($this->ip2hex($REMOTE_ADDR),base64_encode($encode_header_key)).$rn;
            if (isset($HTTP_X_FORWARDED_FOR))
                $header[] = 'X-Squirrel-ProxyHash:'.OneTimePadEncrypt($this->ip2hex($HTTP_X_FORWARDED_FOR),base64_encode($encode_header_key)).$rn;
        } else {
            // use default received headers
            $header[] = "Received: from $received_from" . $rn;
            if ($edit_identity || ! isset($hide_auth_header) || ! $hide_auth_header)
                $header[] = "        (SquirrelMail authenticated user $username)" . $rn;
            $header[] = "        by $SERVER_NAME with HTTP;" . $rn;
            $header[] = "        $date" . $rn;
        }

        /* Insert the rest of the header fields */

        if (!empty($rfc822_header->message_id)) {
            $header[] = 'Message-ID: '. $rfc822_header->message_id . $rn;
        } else {
            $header[] = 'Message-ID: '. $message_id . $rn;
            $rfc822_header->message_id = $message_id;
        }

        if (is_object($reply_rfc822_header) &&
            isset($reply_rfc822_header->message_id) &&
            $reply_rfc822_header->message_id) {
            //if ($reply_rfc822_header->message_id) {
            $rep_message_id = $reply_rfc822_header->message_id;
            $header[] = 'In-Reply-To: '.$rep_message_id . $rn;
            $rfc822_header->in_reply_to = $rep_message_id;
            $references = $this->calculate_references($reply_rfc822_header);
            $header[] = 'References: '.$references . $rn;
            $rfc822_header->references = $references;
        }

        if (!empty($rfc822_header->date) && $rfc822_header->date != -1) {
            $header[] = 'Date: '. $rfc822_header->date . $rn;
        } else {
            $header[] = "Date: $date" . $rn;
            $rfc822_header->date = $date;
        }

        $header[] = 'Subject: '.encodeHeader($rfc822_header->subject) . $rn;

        // folding address list [From|To|Cc|Bcc] happens by using ",$rn<space>"
        // as delimiter
        // Do not use foldLine for that.

        $header[] = 'From: '. $rfc822_header->getAddr_s('from',",$rn ",true) . $rn;

        // RFC2822 if from contains more then 1 address
        if (count($rfc822_header->from) > 1) {
            $header[] = 'Sender: '. $rfc822_header->getAddr_s('sender',',',true) . $rn;
        }
        if (count($rfc822_header->to)) {
            $header[] = 'To: '. $rfc822_header->getAddr_s('to',",$rn ",true) . $rn;
        }
        if (count($rfc822_header->cc)) {
            $header[] = 'Cc: '. $rfc822_header->getAddr_s('cc',",$rn ",true) . $rn;
        }
        if (count($rfc822_header->reply_to)) {
            $header[] = 'Reply-To: '. $rfc822_header->getAddr_s('reply_to',',',true) . $rn;
        }
        /* Sendmail should return true. Default = false */
        $bcc = $this->getBcc();
        if (count($rfc822_header->bcc)) {
            $s = 'Bcc: '. $rfc822_header->getAddr_s('bcc',",$rn ",true) . $rn;
            if (!$bcc) {
                $raw_length += strlen($s);
            } else {
                $header[] = $s;
            }
        }
        /* Identify SquirrelMail */
        $header[] = 'User-Agent: SquirrelMail/' . $version . $rn;
        /* Do the MIME-stuff */
        $header[] = 'MIME-Version: 1.0' . $rn;
        $contenttype = 'Content-Type: '. $rfc822_header->content_type->type0 .'/'.
                                         $rfc822_header->content_type->type1;
        if (count($rfc822_header->content_type->properties)) {
            foreach ($rfc822_header->content_type->properties as $k => $v) {
                if ($k && $v) {
                    $contenttype .= ';' .$k.'='.$v;
                }
            }
        }
        $header[] = $contenttype . $rn;
        if ($encoding = $rfc822_header->encoding) {
            $header[] = 'Content-Transfer-Encoding: ' . $encoding .  $rn;
        }
        if ($rfc822_header->dnt) {
            $dnt = $rfc822_header->getAddr_s('dnt');
            /* Pegasus Mail */
            $header[] = 'X-Confirm-Reading-To: '.$dnt. $rn;
            /* RFC 2298 */
            $header[] = 'Disposition-Notification-To: '.$dnt. $rn;
        }
        if ($rfc822_header->priority) {
            switch($rfc822_header->priority)
            {
            case 1:
                $header[] = 'X-Priority: 1 (Highest)'.$rn;
                $header[] = 'Importance: High'. $rn; break;
            case 3:
                $header[] = 'X-Priority: 3 (Normal)'.$rn;
                $header[] = 'Importance: Normal'. $rn; break;
            case 5:
                $header[] = 'X-Priority: 5 (Lowest)'.$rn;
                $header[] = 'Importance: Low'. $rn; break;
            default: break;
            }
        }
        /* Insert headers from the $more_headers array */
        if(count($rfc822_header->more_headers)) {
            reset($rfc822_header->more_headers);
            foreach ($rfc822_header->more_headers as $k => $v) {
                $header[] = $k.': '.$v .$rn;
            }
        }
        $cnt = count($header);
        $hdr_s = '';

        for ($i = 0 ; $i < $cnt ; $i++) {
            $sKey = substr($header[$i],0,strpos($header[$i],':'));
            switch ($sKey)
            {
            case 'Message-ID':
            case 'In-Reply_To':
                $hdr_s .= $header[$i];
                break;
            case 'References':
                $sRefs = substr($header[$i],12);
                $aRefs = explode(' ',$sRefs);
                $sLine = 'References:';
                foreach ($aRefs as $sReference) {
                    if ( trim($sReference) == '' ) {
                        /* Don't add spaces. */
                    } elseif (strlen($sLine)+strlen($sReference) >76) {
                        $hdr_s .= $sLine;
                        $sLine = $rn . '    ' . $sReference;
                    } else {
                        $sLine .= ' '. $sReference;
                    }
                }
                $hdr_s .= $sLine;
                break;
            case 'To':
            case 'Cc':
            case 'Bcc':
            case 'From':
                $hdr_s .= $header[$i];
                break;
            default: $hdr_s .= $this->foldLine($header[$i], 78); break;
            }
        }
        $header = $hdr_s;
        $header .= $rn; /* One blank line to separate header and body */
        $raw_length += strlen($header);
        return $header;
    }

    /**
     * function foldLine - for cleanly folding of headerlines
     *
     * @param   string  $line
     * @param   integer $length length to fold the line at
     * @param   string  $pre    prefix the line with...
     *
     * @return string   $line folded line with trailing CRLF
     */
    function foldLine($line, $length, $pre='') {
        $line = substr($line,0, -2);
        $length -= 2; /* do not fold between \r and \n */
        $cnt = strlen($line);
        if ($cnt > $length) { /* try folding */
            $fold_string = "\r\n " . $pre;
            $bFirstFold = false;
            $aFoldLine = array();
            while (strlen($line) > $length) {
                $fold = false;
                /* handle encoded parts */
                if (preg_match('/(=\?([^?]*)\?(Q|B)\?([^?]*)\?=)(\s+|.*)/Ui',$line,$regs)) {
                    $fold_tmp = $regs[1];
                    if (!trim($regs[5])) {
                        $fold_tmp .= $regs[5];
                    }
                    $iPosEnc = strpos($line,$fold_tmp);
                    $iLengthEnc = strlen($fold_tmp);
                    $iPosEncEnd = $iPosEnc+$iLengthEnc;
                    if ($iPosEnc < $length && (($iPosEncEnd) > $length)) {
                        $fold = true;
                        /* fold just before the start of the encoded string */
                        if ($iPosEnc) {
                            $aFoldLine[] = substr($line,0,$iPosEnc);
                        }
                        $line = substr($line,$iPosEnc);
                        if (!$bFirstFold) {
                            $bFirstFold = true;
                            $length -= strlen($fold_string);
                        }
                        if ($iLengthEnc > $length) { /* place the encoded
                            string on a separate line and do not fold inside it*/
                            /* minimize foldstring */
                            $fold_string = "\r\n ";
                            $aFoldLine[] = substr($line,0,$iLengthEnc);
                            $line = substr($line,$iLengthEnc);
                        }
                    } else if ($iPosEnc < $length) { /* the encoded string fits into the foldlength */
                        /*remainder */
                        $sLineRem = substr($line,$iPosEncEnd,$length - $iPosEncEnd);
                        if (preg_match('/^(=\?([^?]*)\?(Q|B)\?([^?]*)\?=)(.*)/Ui',$sLineRem) || !preg_match('/[=,;\s]/',$sLineRem)) {
                            /*impossible to fold clean in the next part -> fold after the enc string */
                            $aFoldLine[] = substr($line,0,$iPosEncEnd);
                            $line = substr($line,$iPosEncEnd);
                            $fold = true;
                            if (!$bFirstFold) {
                                $bFirstFold = true;
                                $length -= strlen($fold_string);
                            }
                        }
                    }
                }
                if (!$fold) {
                    $line_tmp = substr($line,0,$length);
                    $iFoldPos = false;
                    /* try to fold at logical places */
                    switch (true)
                    {
                    case ($iFoldPos = strrpos($line_tmp,',')): break;
                    case ($iFoldPos = strrpos($line_tmp,';')): break;
                    case ($iFoldPos = strrpos($line_tmp,' ')): break;
                    case ($iFoldPos = strrpos($line_tmp,'=')): break;
                    default: break;
                    }

                    if (!$iFoldPos) { /* clean folding didn't work */
                        $iFoldPos = $length;
                    }
                    $aFoldLine[] = substr($line,0,$iFoldPos+1);
                    $line = substr($line,$iFoldPos+1);
                    if (!$bFirstFold) {
                        $bFirstFold = true;
                        $length -= strlen($fold_string);
                    }
                }
            }
            /*$reconstruct the line */
            if ($line) {
                $aFoldLine[] = $line;
            }
            $line = implode($fold_string,$aFoldLine);
        }
        return $line."\r\n";
    }

    /**
     * function mimeBoundary - calculates the mime boundary to use
     *
     * This function will generate a random mime boundary base part
     * for the message if the boundary has not already been set.
     *
     * @return string $mimeBoundaryString random mime boundary string
     */
    function mimeBoundary () {
        static $mimeBoundaryString;

        if ( !isset( $mimeBoundaryString ) ||
            $mimeBoundaryString == '') {
            $mimeBoundaryString = '----=_' . date( 'YmdHis' ) . '_' .
            mt_rand( 10000, 99999 );
        }
        return $mimeBoundaryString;
    }

    /**
     * function timezone - Time offset for correct timezone
     *
     * @return string $result with timezone and offset
     */
    function timezone () {
        global $invert_time, $show_timezone_name;

        $diff_second = date('Z');
        if ($invert_time) {
            $diff_second = - $diff_second;
        }
        if ($diff_second > 0) {
            $sign = '+';
        } else {
            $sign = '-';
        }
        $diff_second = abs($diff_second);
        $diff_hour = floor ($diff_second / 3600);
        $diff_minute = floor (($diff_second-3600*$diff_hour) / 60);

        // If an administrator wants to add the timezone name to the
        // end of the date header, they can set $show_timezone_name
        // to boolean TRUE in config/config_local.php, but that is
        // NOT RFC-822 compliant (see section 5.1).  Moreover, some
        // Windows users reported that strftime('%Z') was returning
        // the full zone name (not the abbreviation) which in some
        // cases included 8-bit characters (not allowed as is in headers).
        // The PHP manual actually does NOT promise what %Z will return
        // for strftime!:  "The time zone offset/abbreviation option NOT
        // given by %z (depends on operating system)"
        //
        if ($show_timezone_name) {
            $zonename = '('.strftime('%Z').')';
            $result = sprintf ("%s%02d%02d %s", $sign, $diff_hour, $diff_minute, $zonename);
        } else {
            $result = sprintf ("%s%02d%02d", $sign, $diff_hour, $diff_minute);
        }
        return ($result);
    }

    /**
     * function calculate_references - calculate correct References string
     * Adds the current message ID, and makes sure it doesn't grow forever,
     * to that extent it drops message-ID's in a smart way until the string
     * length is under the recommended value of 1000 ("References: <986>\r\n").
     * It always keeps the first and the last three ID's.
     *
     * @param   Rfc822Header $hdr    message header to calculate from
     *
     * @return  string       $refer  concatenated and trimmed References string
     */
    function calculate_references($hdr) {
        $aReferences = preg_split('/\s+/', $hdr->references);
        $message_id = $hdr->message_id;
        $in_reply_to = $hdr->in_reply_to;
	
        // if References already exists, add the current message ID at the end.
        // no References exists; if we know a IRT, add that aswell
        if (count($aReferences) == 0 && $in_reply_to) {
            $aReferences[] = $in_reply_to;
        }
        $aReferences[] = $message_id;

        // sanitize the array: trim whitespace, remove dupes
        array_walk($aReferences, 'sq_trim_value');
        $aReferences = array_unique($aReferences);

        while ( count($aReferences) > 4 && strlen(implode(' ', $aReferences)) >= 986 ) {
            $aReferences = array_merge(array_slice($aReferences,0,1),array_slice($aReferences,2));
        }
        return implode(' ', $aReferences);
    }

    /**
     * Converts ip address to hexadecimal string
     *
     * Function is used to convert ipv4 and ipv6 addresses to hex strings.
     * It removes all delimiter symbols from ip addresses, converts decimal
     * ipv4 numbers to hex and pads strings in order to present full length
     * address. ipv4 addresses are represented as 8 byte strings, ipv6 addresses
     * are represented as 32 byte string.
     *
     * If function fails to detect address format, it returns unprocessed string.
     * @param string $string ip address string
     * @return string processed ip address string
     * @since 1.5.1 and 1.4.5
     */
    function ip2hex($string) {
        if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/",$string,$match)) {
            // ipv4 address
            $ret = str_pad(dechex($match[1]),2,'0',STR_PAD_LEFT)
                . str_pad(dechex($match[2]),2,'0',STR_PAD_LEFT)
                . str_pad(dechex($match[3]),2,'0',STR_PAD_LEFT)
                . str_pad(dechex($match[4]),2,'0',STR_PAD_LEFT);
        } elseif (preg_match("/^([0-9a-h]+)\:([0-9a-h]+)\:([0-9a-h]+)\:([0-9a-h]+)\:([0-9a-h]+)\:([0-9a-h]+)\:([0-9a-h]+)\:([0-9a-h]+)$/i",$string,$match)) {
            // full ipv6 address
            $ret = str_pad($match[1],4,'0',STR_PAD_LEFT)
                . str_pad($match[2],4,'0',STR_PAD_LEFT)
                . str_pad($match[3],4,'0',STR_PAD_LEFT)
                . str_pad($match[4],4,'0',STR_PAD_LEFT)
                . str_pad($match[5],4,'0',STR_PAD_LEFT)
                . str_pad($match[6],4,'0',STR_PAD_LEFT)
                . str_pad($match[7],4,'0',STR_PAD_LEFT)
                . str_pad($match[8],4,'0',STR_PAD_LEFT);
        } elseif (preg_match("/^\:\:([0-9a-h\:]+)$/i",$string,$match)) {
            // short ipv6 with all starting symbols nulled
            $aAddr=explode(':',$match[1]);
            $ret='';
            foreach ($aAddr as $addr) {
                $ret.=str_pad($addr,4,'0',STR_PAD_LEFT);
            }
            $ret=str_pad($ret,32,'0',STR_PAD_LEFT);
        } elseif (preg_match("/^([0-9a-h\:]+)::([0-9a-h\:]+)$/i",$string,$match)) {
            // short ipv6 with middle part nulled
            $aStart=explode(':',$match[1]);
            $sStart='';
            foreach($aStart as $addr) {
                $sStart.=str_pad($addr,4,'0',STR_PAD_LEFT);
            }
            $aEnd = explode(':',$match[2]);
            $sEnd='';
            foreach($aEnd as $addr) {
                $sEnd.=str_pad($addr,4,'0',STR_PAD_LEFT);
            }
            $ret = $sStart
                . str_pad('',(32 - strlen($sStart . $sEnd)),'0',STR_PAD_LEFT)
                . $sEnd;
        } else {
            // unknown addressing
            $ret = $string;
        }
        return $ret;
    }
}

