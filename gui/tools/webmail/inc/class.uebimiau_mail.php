<?
class UebiMiau extends UebiMiau_core {

	var $_current_folder 	= "";
	var $_sysmap			= Array(
								"inbox" 	=> "inbox",
								"trash" 	=> "trash",
								"sent" 		=> "sent");

	var $_system_folders 	= Array(
								"inbox",
								"trash",
								"sent");
	
	var $_boxes				= Array();
	var $CRLF				= "\r\n";

	function UebiMiau() {
		require("./inc/class.tnef.php");
		$this->_tnef = new TNEF();
		$this->_sid = uniqid("");
	}

	function mail_connected() {
        if(!empty($this->mail_connection)) {
            $sock_status = @socket_get_status($this->mail_connection);
			
            if($sock_status["eof"]) {
                @fclose($this->mail_connection);
                return 0;
            }
            return 1; 
        } 
        return 0;
	}

	function mail_get_line() {
		$buffer = @fgets($this->mail_connection, 8192);
		$buffer = preg_replace("/\r?\n/","\r\n",$buffer);
		if($this->debug) {
			$sendtodebug = true;
			if(eregi("^(\\* )",$buffer) || eregi("^([A-Za-z0-9]+ (OK|NO|BAD))",$buffer) || eregi("^(\\+OK|\\-ERR)",$buffer)) {
				$output = "<- <b>".htmlspecialchars($buffer)."</b>";
			} else {
				$sendtodebug = ($this->debug > 1)?false:true;
				$output = htmlspecialchars($buffer);
			}
			if ($sendtodebug)
				echo("<font style=\"font-size:12px; font-family: Courier New; background-color: white; color: black;\"> $output</font><br>\r\n");
			flush();
		}
		return $buffer;
	}

	function mail_send_command($cmd) {

		if($this->mail_connected()) {
			$output = (eregi("^(PASS|LOGIN)",$cmd,$regs))?$regs[1]." ****":$cmd;
			if($this->mail_protocol == "imap") {
				$cmd = $this->_sid." ".$cmd;
				$output = $this->_sid." ".$output;
			}
			fwrite($this->mail_connection, $cmd);
			if($this->debug) {
				echo("<font style=\"font-size:12px; font-family: Courier New; background-color: white; color: black;\">-&gt; <em><b>".htmlspecialchars($output)."</b></em></font><br>\r\n");
				flush();
			}
			return 1;
		}
		return 0;
	}

	function mail_connect() {
		if($this->debug)
			for($i=0;$i<20;$i++)
				echo("<!-- buffer sux -->\r\n");
		if(!$this->mail_connected()) {
			$this->mail_connection = fsockopen($this->mail_server, $this->mail_port, $errno, $errstr, 15);
			if($this->mail_connection) {
				$buffer = $this->mail_get_line();
				if($this->mail_protocol == "imap") $regexp = "^([ ]?\\*[ ]?OK)";
				else $regexp = "^(\\+OK)";
				if(ereg($regexp,$buffer)) return 1;
				else return 0;
			}
			return 0;
		} else return 1;
	}


	function mail_auth($checkfolders=false) {
		if($this->mail_connected()) {
			if ($this->mail_protocol == "imap") {
				$this->mail_send_command("LOGIN ".$this->mail_user." ".$this->mail_pass.$this->CRLF);
				$buffer = $this->mail_get_line();
				if(ereg("^(".$this->_sid." OK)",$buffer)) { 
					$this->_update_sysmap();
					if($checkfolders)
						$this->_check_folders();
					return 1;
				} else { 
					$this->mail_error_msg = $buffer; 
					return 0; 
				}
			} else {
				$this->mail_send_command("USER ".$this->mail_user.$this->CRLF);
				$buffer = $this->mail_get_line();
				if(ereg("^(\+OK)",$buffer)) {
					$this->mail_send_command("PASS ".$this->mail_pass.$this->CRLF);
					$buffer = $this->mail_get_line();
					if(ereg("^(\+OK)",$buffer)) { 
						$this->_update_sysmap();
						if($checkfolders)
							$this->_check_folders();
						return 1;
					} else { 
						$this->mail_error_msg = $buffer; 
						return 0; 
					}
				} else 
					return 0;
			}
		}
		return 0;
	}

	function _update_sysmap() {
		if(!file_exists($this->user_folder))
			mkdir($this->user_folder,0777);

		$boxes 			= $this->mail_list_boxes();
		$sysmap			= $this->_sysmap;
		$sysfolders 	= $this->_system_folders;
		
		
		for($i=0;$i<count($boxes); $i++) {
			$current_folder = $boxes[$i]["name"];
			if(in_array(strtolower($current_folder), $sysfolders)) 
				$sysmap[strtolower($current_folder)] = $current_folder;
		}
		$this->_boxes	= $boxes;
		$this->_sysmap	= $sysmap;

	}

	function get_system_folders() {
		return $this->_sysmap;
	}

	function _check_folders() {

		$userfolder 			= $this->user_folder;
		$temporary_directory 	= $this->temp_folder;
		$idle_timeout 			= $this->timeout;

		$boxes 			= $this->_boxes;
		$sysfolders 	= Array();
		$tmp 			= Array();

		while(list($key,$value) = each($this->_sysmap))
			$sysfolders[] = $value;

		$tmp = $sysfolders;

		if($this->mail_protocol == "imap") {

			for($i=0;$i<count($boxes);$i++) {
				$current_folder = $boxes[$i]["name"];
				while(list($index,$value) = each($tmp)) 
					if($current_folder == $value) 
						unset($tmp[$index]);
				reset($tmp);
			}
			
			while(list($index,$value) = each($tmp)) {
				$this->mail_create_box($this->fix_prefix($value,1));
			}
			
			for($i=0;$i<count($boxes);$i++) {
				$current_folder = $boxes[$i]["name"];
				if(!in_array($current_folder, $sysfolders)) {
					if(!file_exists($this->user_folder.$current_folder)) 
						mkdir($this->user_folder.$current_folder,0777);
				}
			}
		}

		$system_folders = array_merge($sysfolders, Array("_attachments","_infos"));
		
		while(list($index,$value) = each($system_folders)) 
			if(!file_exists($this->user_folder.$value)) 
				mkdir($this->user_folder.$value,0777);

	}
	
	
	function mail_retr_msg($msg, $check=1) {

		global $mail_use_top,$error_retrieving;
		$msgheader = $msg["header"];

		if($this->mail_protocol == "imap") {

			if($check) {
				if($this->_current_folder != $msg["folder"])
					$boxinfo = $this->mail_select_box($msg["folder"]);
				$this->mail_send_command("FETCH ".$msg["id"].":".$msg["id"]." BODY.PEEK[HEADER.FIELDS (Message-Id)]".$this->CRLF);
				$buffer = chop($this->mail_get_line());
				if(eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
				while(!eregi("^(".$this->_sid." OK)",$buffer)) {
					if(preg_match("/message-id: (.*)/i",$buffer,$regs))
						$current_id = ereg_replace("<(.*)>","\\1",$regs[1]);
					$buffer = chop($this->mail_get_line());
				}
				
				
				if(base64_encode($current_id) != base64_encode($msg["message-id"])) {
					$this->mail_error_msg = $error_retrieving;
					return 0;
				}
			}

			if(file_exists($msg["localname"])) {
				$msgcontent = $this->_read_file($msg["localname"]);
			} else {
				$this->mail_send_command("FETCH ".$msg["id"].":".$msg["id"]." BODY[TEXT]".$this->CRLF);
				$buffer = $this->mail_get_line();
				if(eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
				if(ereg("\\{(.*)\\}",$buffer,$regs))
					$bytes = $regs[1];

				$buffer = $this->mail_get_line();
				while(!eregi("^(".$this->_sid." OK)",$buffer)) {
					if(!eregi("[ ]?\\*[ ]?[0-9]+[ ]?FETCH",$buffer))
						$msgbody .= $buffer;
					$buffer = $this->mail_get_line();
				}
				$pos = strrpos($msgbody, ")");
				if(!($pos === false))
					$msgbody = substr($msgbody,0,$pos);

				$msgcontent = "$msgheader\r\n\r\n$msgbody";

				$this->_save_file($msg["localname"],$msgcontent);

			}

		} else {

			if($check && $msg["folder"] == $this->_sysmap["inbox"]) {
				$this->mail_send_command("TOP ".$msg["id"]." 0".$this->CRLF);
				$buffer = $this->mail_get_line();

				if(!ereg("^(\+OK)",$buffer))  { $this->mail_error_msg = $buffer; return 0; }

				unset($header);
				
				
				while (!feof($this->mail_connection)) {
					$buffer = $this->mail_get_line();
					if(trim($buffer) == ".") break;
					$header .= $buffer;
				}
				$mail_info = $this->get_mail_info($header);
				
				if(base64_encode($mail_info["message-id"]) != base64_encode($msg["message-id"])) {
					$this->mail_error_msg = $error_retrieving;
					return 0;
				}
			}

			
			if(file_exists($msg["localname"])) {
				$msgcontent = $this->_read_file($msg["localname"]);
			} elseif ($msg["folder"] == $this->_sysmap["inbox"]) {

				$command = ($mail_use_top)?"TOP ".$msg["id"]." ".$msg["size"]:"RETR ".$msg["id"];
				$this->mail_send_command($command.$this->CRLF);
				
				$buffer = $this->mail_get_line();
				if(!ereg("^(\+OK)",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
				$last_buffer = 0;
				$msgcontent = "";

				while (!feof($this->mail_connection)) {
					$buffer = $this->mail_get_line();
					if(chop($buffer) == ".") break;
					$msgcontent .= $buffer;
				}

				$this->_save_file($msg["localname"],$msgcontent);
			}
		}
		
		return $msgcontent;
	}


	function mail_delete_msg($msg, $send_to_trash = 1, $save_only_read = 0) {

		$read = (ereg("\\SEEN",$msg["flags"]))?1:0;

		/* choose your protocol */
		if($this->mail_protocol == "imap") {
			
			
			/* check the message id to make sure that the messages still in the server */
			if($this->_current_folder != $msg["folder"])
				$boxinfo = $this->mail_select_box($msg["folder"]);
	
			$this->mail_send_command("FETCH ".$msg["id"].":".$msg["id"]." BODY.PEEK[HEADER.FIELDS (Message-Id)]".$this->CRLF);
			$buffer = chop($this->mail_get_line());

			/* if any problem with the server, stop the function */
			if(eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { $this->mail_error_msg = $buffer; return 0; }

			while(!eregi("^(".$this->_sid." OK)",$buffer)) {
				/* we need only the message id yet */

				if(eregi("message-id: (.*)",$buffer,$regs))
					$current_id = ereg_replace("<(.*)>","\\1",$regs[1]);

				$buffer = chop($this->mail_get_line());
			}


			/* compare the old and the new message id, if different, stop*/
			if(base64_encode($current_id) != base64_encode($msg["message-id"])) {
				$this->mail_error_msg = $error_retrieving;
				return 0;
			}

			/*if the pointer is here, no one problem occours*/

			
			if( $send_to_trash && 
				strtoupper($msg["folder"]) != "TRASH" &&
				(!$save_only_read || ($save_only_read && $read))) {

				$trash_folder = $this->fix_prefix($this->_sysmap["trash"],1);

				$this->mail_send_command("COPY ".$msg["id"].":".$msg["id"]." \"$trash_folder\"".$this->CRLF);
				$buffer = $this->mail_get_line();

				/* if any problem with the server, stop the function */
				if(!eregi("^(".$this->_sid." OK)",$buffer)) { $this->mail_error_msg = $buffer; return 0; }

				if(file_exists($msg["localname"])) {
					$currentname = $msg["localname"];
					$basename = basename($currentname);
					$newfilename = $this->user_folder.$this->_sysmap["trash"]."/$basename";
					copy($currentname,$newfilename);
					unlink($currentname);
				}
			}
			$this->mail_set_flag($msg,"\\DELETED","+");

			$this->_require_expunge = true;

			return 1;

		} else {
			/* now we are working with POP3 */
			/* check the message id to make sure that the messages still in the server */
			if(strtoupper($msg["folder"]) == "INBOX") {

				$this->mail_send_command("TOP ".$msg["id"]." 0".$this->CRLF);
				$buffer = $this->mail_get_line();
	
				/* if any problem with the server, stop the function */
				if(!ereg("^(\+OK)",$buffer))  { $this->mail_error_msg = $buffer; return 0; }
	
				unset($header);
	
				while (!feof($this->mail_connection)) {
					$buffer = $this->mail_get_line();
					if(trim($buffer) == ".") break;
					$header .= $buffer;
				}
				$mail_info = $this->get_mail_info($header);
	
	
				/* compare the old and the new message id, if different, stop*/
				if(base64_encode($mail_info["message-id"]) != base64_encode($msg["message-id"])) {
					$this->mail_error_msg = $error_retrieving;
					return 0;
				}

				if(!file_exists($msg["localname"])) {
					if(!$this->mail_retr_msg($msg,0)) return 0;
					$this->mail_set_flag($msg,"\\SEEN","-");
				}
				$this->mail_send_command("DELE ".$msg["id"].$this->CRLF);
				$buffer = $this->mail_get_line();
				if(!ereg("^(\+OK)",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
			}

			if( $send_to_trash && 
				strtoupper($msg["folder"]) != "TRASH" &&
				(!$save_only_read || ($save_only_read && $read))) {

				if(file_exists($msg["localname"])) {
					$currentname = $msg["localname"];
					$basename = basename($currentname);
					$newfilename = $this->user_folder.$this->_sysmap["trash"]."/$basename";
					copy($currentname,$newfilename);
					unlink($currentname);
				}
			} else {
				unlink($msg["localname"]);
			}

		}
		return 1;
	}


	function mail_move_msg($msg,$tofolder) {

		/* choose your protocol */
		$tofolder = $this->fix_prefix($tofolder,1);
		if($this->mail_protocol == "imap") {

			if($tofolder != $msg["folder"]) {
				/* check the message id to make sure that the messages still in the server */
				if($this->_current_folder != $msg["folder"])
					$boxinfo = $this->mail_select_box($msg["folder"]);
		
				$this->mail_send_command("FETCH ".$msg["id"].":".$msg["id"]." BODY.PEEK[HEADER.FIELDS (Message-Id)]".$this->CRLF);
				$buffer = chop($this->mail_get_line());
	
				/* if any problem with the server, stop the function */
				if(eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
	
				while(!eregi("^(".$this->_sid." OK)",$buffer)) {
					/* we need only the message id yet */
	
					if(eregi("message-id: (.*)",$buffer,$regs))
						$current_id = ereg_replace("<(.*)>","\\1",$regs[1]);
	
					$buffer = chop($this->mail_get_line());
				}

				/* compare the old and the new message id, if different, stop*/
				if(base64_encode($current_id) != base64_encode($msg["message-id"])) {
					$this->mail_error_msg = $error_retrieving;
					return 0;
				}

				$tofolder = $this->fix_prefix($tofolder,1);
				
				$this->mail_send_command("COPY ".$msg["id"].":".$msg["id"]." \"$tofolder\"".$this->CRLF);
				$buffer = $this->mail_get_line();

				/* if any problem with the server, stop the function */
				if(!eregi("^(".$this->_sid." OK)",$buffer)) { $this->mail_error_msg = $buffer; return 0; }

				if(file_exists($msg["localname"])) {
					$currentname = $msg["localname"];
					$basename = basename($currentname);
					$newfilename = $this->user_folder."$tofolder/$basename";
					copy($currentname,$newfilename);
					unlink($currentname);
				}
				$this->mail_set_flag($msg,"\\DELETED","+");
				$this->_require_expunge = true;
			}

			return 1;

		} else {

			if($tofolder != $this->_sysmap["inbox"] && $tofolder != $msg["folder"]) {
				/* now we are working with POP3 */
				/* check the message id to make sure that the messages still in the server */
				if($msg["folder"] == $this->_sysmap["inbox"]) {
	
					$this->mail_send_command("TOP ".$msg["id"]." 0".$this->CRLF);
					$buffer = $this->mail_get_line();
		
					/* if any problem with the server, stop the function */
					if(!ereg("^(\+OK)",$buffer))  { $this->mail_error_msg = $buffer; return 0; }
		
					unset($header);
		
					while (!feof($this->mail_connection)) {
						$buffer = $this->mail_get_line();
						if(trim($buffer) == ".") break;
						$header .= $buffer;
					}
					$mail_info = $this->get_mail_info($header);
		
		
					/* compare the old and the new message id, if different, stop*/
					if(base64_encode($mail_info["message-id"]) != base64_encode($msg["message-id"])) {
						$this->mail_error_msg = $error_retrieving;
						return 0;
					}

					if(!file_exists($msg["localname"])) {
						if(!$this->mail_retr_msg($msg,0)) 
							return 0;
						$this->mail_set_flag($msg,"\\SEEN","-");
					}

					$this->mail_send_command("DELE ".$msg["id"].$this->CRLF);
					$buffer = $this->mail_get_line();

					if(!ereg("^(\+OK)",$buffer)) { 
						$this->mail_error_msg = $buffer; 
						return 0; 
					}
				}
				
				if(file_exists($msg["localname"])) {
					$currentname = $msg["localname"];
					$basename = basename($currentname);
					$newfilename = $this->user_folder."$tofolder/$basename";
					copy($currentname,$newfilename);
					unlink($currentname);
				}
			} else 
				return 0;
			
		}
		return 1;
	}

	function mail_list_msgs($boxname = "INBOX") {

		global $userfolder;
		$messages = Array();

		/* choose the protocol */

		if($this->mail_protocol == "imap") {

			/* select the mail box and make sure that it exists */
			$boxinfo = $this->mail_select_box($boxname);

			if(is_array($boxinfo) && $boxinfo["exists"]) {

				/* if the box is ok, fetch the first to the last message, getting the size and the header */
	
				$this->mail_send_command("FETCH 1:".$boxinfo["exists"]." (FLAGS RFC822.SIZE RFC822.HEADER)".$this->CRLF);
				$buffer = $this->mail_get_line();
	
				/* if any problem, stop the procedure */
	
				if(!eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { 
	
					$counter = 0;
					
					/* the end mark is <sid> OK FETCH, we are waiting for it*/
					while(!eregi("^(".$this->_sid." OK)",$buffer)) {
						/* if the return is something such as * N FETCH, a new message will displayed  */
						if(eregi("[ ]?\\*[ ]?([0-9]+)[ ]?FETCH",$buffer,$regs)) {
							$curmsg	= $regs[1];
							eregi("SIZE[ ]?([0-9]+)",$buffer,$regs);
							$size	= $regs[1];
							eregi("FLAGS[ ]?\\((.*)\\)",$buffer,$regs);
							$flags 	= $regs[1];
						/* if any problem, add the current line to buffer */
						} elseif(trim($buffer) != ")" && trim($buffer) != "" && !eregi("[ ]?\\*[ ]?NO",$buffer)) {
							$header .= $buffer;
		
						/*	the end of message header was reached, increment the counter and store the last message */
						} elseif(trim($buffer) == ")") {
							$messages[$counter]["id"] = $curmsg; //$counter+1; //$msgs[0];
							$messages[$counter]["msg"] = $curmsg;
							$messages[$counter]["size"] = $size;
							$messages[$counter]["flags"] = strtoupper($flags);
							$messages[$counter]["header"] = $header;
							$counter++;
							$header = "";
						}
						$buffer = $this->mail_get_line();
					}
				}
			}
		} else {
			/* 
			now working with POP3
			if the boxname is "INBOX", we can check in the server for messsages 
			*/
			if(strtoupper($boxname) == "INBOX") {
				$this->mail_send_command("LIST".$this->CRLF);
				$buffer = $this->mail_get_line();
				/* if any problem with this messages list, stop the procedure */

				if(!ereg("^(\+OK)",$buffer))  { $this->mail_error_msg = $buffer; return $messages; }

				$counter = 0;

				while (!feof($this->mail_connection)) {
					$buffer = $this->mail_get_line();
					if(trim($buffer) == ".") break;
					$msgs = split(" ",$buffer);
					if(is_numeric($msgs[0])) {
						$messages[$counter]["id"] = $counter+1; //$msgs[0];
						$messages[$counter]["msg"] = trim($msgs[0]);
						$messages[$counter]["size"] = trim($msgs[1]);
						$counter++;
					}
				}

				/* OK, now we have id and size of messages, but we need the headers too */
				if(count($messages) == 0) return $messages;
	
				for($i=0;$i<count($messages);$i++) {
					$this->mail_send_command("TOP ".$messages[$i]["msg"]." 0".$this->CRLF);
					$buffer = $this->mail_get_line();
					/* if any problem with this messages list, stop the procedure */
					if(!ereg("^(\+OK)",$buffer))  { $this->mail_error_msg = $buffer; return 0; }

					while (!feof($this->mail_connection)) {
						$buffer = $this->mail_get_line();
						if(trim($buffer) == ".") break;
						if(strlen($buffer) > 3) 
							$header .= $buffer;
					}
					if(!($pos = strpos($header,"\r\n\r\n") === false)) 
						$header = substr($header,0,$pos);

					$messages[$i]["header"] = $header;
					$header = "";

				}
			} else {
				/* otherwise, we need get the message list from a cache (currently, hard disk)*/
				$datapath = $userfolder.$boxname;
				$i = 0;
				$messages = Array();
				$d = dir($datapath);
				$dirsize = 0;

				while($entry=$d->read()) {
					$fullpath = "$datapath/$entry";
					if(is_file($fullpath)) {
						$thisheader = $this->_get_headers_from_cache($fullpath);
						$messages[$i]["id"]			= $i+1;
						$messages[$i]["msg"]			= $i;
						$messages[$i]["header"]		= $thisheader;
						$messages[$i]["size"]		= filesize($fullpath);
						$messages[$i]["localname"]	= $fullpath;
						$i++;
					}
				}

				$d->close();
			}
		}


		/* 
		OK, now we have the message list, that contains id, size and header
		this script will process the header to get subject, date and other
		informations formatted to be displayed in the message list when  needed
		*/

		for($i=0;$i<count($messages);$i++) {
			$mail_info = $this->get_mail_info($messages[$i]["header"]);
			$messages[$i]["date"] = $mail_info["date"];
			$messages[$i]["subject"] = $mail_info["subject"];
			$messages[$i]["message-id"] = $mail_info["message-id"];
			$messages[$i]["from"] = $mail_info["from"];
			$messages[$i]["to"] = $mail_info["to"];
			$messages[$i]["fromname"] = $mail_info["from"][0]["name"];
			$messages[$i]["to"] = $mail_info["to"];
			$messages[$i]["cc"] = $mail_info["cc"];
			$messages[$i]["headers"] = $header;
			$messages[$i]["priority"] = $mail_info["priority"];
			$messages[$i]["attach"] = (eregi("(multipart/mixed|multipart/related|application)",$mail_info["content-type"]))?1:0;

			if ($messages[$i]["localname"] == "") {
				$messages[$i]["localname"] = $this->_get_local_name($mail_info,$boxname);
			}

			$messages[$i]["read"] = file_exists($flocalname)?1:0;

			/* 
			ops, a trick. if the message is not imap, the flags are stored in
			a special field on headers 
			*/

			if($this->mail_protocol != "imap" && file_exists($messages[$i]["localname"])) {

				$headers = $this->_get_headers_from_cache($messages[$i]["localname"]);
				$headers = $this->decode_header($headers);
				$messages[$i]["flags"] = strtoupper($headers["x-um-flags"]);
			}
			
			$messages[$i]["folder"] = $boxname;
		}
		return $messages;
	}

	function _get_local_name($message,$boxname) {
		$flocalname = trim($this->user_folder.$boxname."/".md5(trim($message["subject"].$message["date"].$message["message-id"])).".eml");
		return $flocalname;
	}

	function mail_list_boxes($boxname = "*") {

		$boxlist = Array();
		/* choose the protocol*/
		if($this->mail_protocol == "imap") {
			$this->mail_send_command("LIST \"\" $boxname".$this->CRLF);
			$buffer = $this->mail_get_line();
			/* if any problem, stop the script */
			if(eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
			/* loop throught the list and split the parts */
			while(!eregi("^(".$this->_sid." OK)",$buffer)) {
				$tmp = Array();
				ereg("\\((.*)\\)",$buffer,$regs);
				$flags = $regs[1];
				$tmp["flags"] = $flags;

				ereg("\\((.*)\\)",$buffer,$regs);
				$flags = $regs[1];
				
				$pos = strpos($buffer,")");
				$rest = substr($buffer,$pos+2);
				$pos = strpos($rest," ");
				$tmp["prefix"] = ereg_replace("\"(.*)\"","\\1",substr($rest,0,$pos));
				$tmp["name"] = $this->fix_prefix(trim(ereg_replace("\"(.*)\"","\\1",substr($rest,$pos+1))),0);
				$buffer = $this->mail_get_line();
				$boxlist[] = $tmp;
			}
		} else {
			/* if POP3, only list the available folders */

			$d = dir($this->user_folder);
			while($entry=$d->read()) {

				if(	is_dir($this->user_folder.$entry) && 
					$entry != ".." && 
					substr($entry,0,1) != "_" && 
					$entry != ".") {
					$boxlist[]["name"] = $entry;
				}
			}
			$d->close();
		}
		return $boxlist;
	}

	function mail_select_box($boxname = "INBOX") {
		/* this function is used only for IMAP servers */
		if($this->mail_protocol == "imap") {
			$original_name = ereg_replace("\"(.*)\"","\\1", $boxname);
			$boxname = $this->fix_prefix($original_name,1);
			$this->mail_send_command("SELECT \"$boxname\"".$this->CRLF);
			$buffer = $this->mail_get_line();
			if(preg_match("/^".$this->_sid." NO/i",$buffer)) { 
				if($this->mail_subscribe_box($original_name)) {
					$this->mail_send_command("SELECT \"$boxname\"".$this->CRLF);
					$buffer = $this->mail_get_line();
				}
			}
			if(eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
			$boxinfo = Array();
			/* get total, recent messages and flags */
			while(!eregi("^(".$this->_sid." OK)",$buffer)) {
				if(eregi("[ ]?\\*[ ]?([0-9]+)[ ]EXISTS",$buffer,$regs))
					$boxinfo["exists"] = $regs[1];
				if(eregi("[ ]?\\*[ ]?([0-9])+[ ]RECENT",$buffer,$regs))
					$boxinfo["recent"] = $regs[1];
				if(eregi("[ ]?\\*[ ]?FLAGS[ ]?\\((.*)\\)",$buffer,$regs))
					$boxinfo["flags"] = $regs[1];
				$buffer = $this->mail_get_line();
			}
		}
		$this->_current_folder = $boxname;
		return $boxinfo;
	}


	function mail_subscribe_box($boxname = "INBOX") {
		/* this function is used only for IMAP servers */
		if($this->mail_protocol == "imap") {
			$boxname = $this->fix_prefix(ereg_replace("\"(.*)\"","\\1",$boxname),1);
			$this->mail_send_command("SUBSCRIBE \"$boxname\"".$this->CRLF);
			$buffer = $this->mail_get_line();
			if(preg_match("/^".$this->_sid." (NO|BAD)/i",$buffer)) { 
				$this->mail_error_msg = $buffer; 
				return 0; 
			}
		}
		return 1;
	}


	function mail_create_box($boxname) {
		if($this->mail_protocol == "imap") {
			$boxname = $this->fix_prefix(ereg_replace("\"(.*)\"","\\1",$boxname),1);
			$this->mail_send_command("CREATE \"$boxname\"".$this->CRLF);
			$buffer = $this->mail_get_line();
			if(eregi("^(".$this->_sid." OK)",$buffer)) {
				@mkdir($this->user_folder.$this->fix_prefix($boxname,0),0777);
				return 1;
			} else { 
				$this->mail_error_msg = $buffer; return 0; 
			}

		} else {
			/* if POP3, only make a new folder */
			if(@mkdir($this->user_folder.$boxname,0777)) return 1;
			else return 0;

		}
	}

	function mail_delete_box($boxname) {
		if($this->mail_protocol == "imap") {
			$boxname = $this->fix_prefix(ereg_replace("\"(.*)\"","\\1",$boxname),1);
			$this->mail_send_command("DELETE \"$boxname\"".$this->CRLF);
			$buffer = $this->mail_get_line();

			if(eregi("^(".$this->_sid." OK)",$buffer)) {
				$this->_RmDirR($this->user_folder.$boxname);
				return 1;
			} else { 
				$this->mail_error_msg = $buffer; 
				return 0; 
			}

		} else {
			if(is_dir($this->user_folder.$boxname)) {
				$this->_RmDirR($this->user_folder.$boxname);
				return 1;
			} else {
				return 0;
			}
		}
	}


	function mail_save_message($boxname,$message,$flags = "") {
		if($this->mail_protocol == "imap") {
			$boxname = $this->fix_prefix(ereg_replace("\"(.*)\"","\\1",$boxname),1);
			$this->mail_send_command("APPEND \"$boxname\" ($flags) {".strlen($message)."}".$this->CRLF."$message".$this->CRLF);
			$buffer = $this->mail_get_line();
			if($buffer[0] == "+") {
				$this->mail_send_command($this->CRLF);
				$buffer = $this->mail_get_line();
			}
			if(!eregi("^(".$this->_sid." OK)",$buffer)) return 0; 
		}

		if(is_dir($this->user_folder.$boxname)) {
			$email = $this->fetch_structure($message);
			$mail_info = $this->get_mail_info($email["header"]);
			$filename = $this->_get_local_name($mail_info,$boxname);
			if(!empty($flags))
				$message = trim($email["header"])."\r\nX-UM-Flags: $flags\r\n\r\n".$email["body"];
			unset($email);
			$this->_save_file($filename,$message);
			return 1;
		}
	}

	function mail_set_flag(&$msg,$flagname,$flagtype = "+") {
		$flagname = strtoupper($flagname);
		if($flagtype == '+' && strstr($msg['flags'], $flagname))
			return true;
		if($flagtype == '-' && !strstr($msg['flags'], $flagname))
			return true;

		if($this->mail_protocol == "imap") {
			if($this->_current_folder != $msg["folder"])
				$this->mail_select_box($msg["folder"]);
			if($flagtype != "+") $flagtype = "-";
			$this->mail_send_command("STORE ".$msg["id"].":".$msg["id"]." ".$flagtype."FLAGS ($flagname)".$this->CRLF);
			$buffer = $this->mail_get_line();
			while(!eregi("^(".$this->_sid." (OK|NO|BAD))",$buffer)) { 
				$buffer = $this->mail_get_line();
			}
			if(!eregi("^(".$this->_sid." OK)",$buffer)) { $this->mail_error_msg = $buffer; return 0;}

		} elseif (!file_exists($msg["localname"]))
			$this->mail_retr_msg($msg,0);

		if(file_exists($msg["localname"])) {

			$email 		= $this->_read_file($msg["localname"]);
			$email		= $this->fetch_structure($email);
			$header 	= $email["header"];
			$body	 	= $email["body"];
			$headerinfo	= $this->decode_header($header);

			$strFlags 	= trim(strtoupper($msg["flags"]));

			$flags = Array();
			if(!empty($strFlags))
				$flags = split(" ",$strFlags);

			if($flagtype == "+") {
				if(!in_array($flagname,$flags))
					$flags[] = $flagname;
			} else {
				while(list($key,$value) = each($flags))
					if(strtoupper($value) == $flagname) 
						$pos = $key;
				if(isset($pos)) unset($flags[$pos]);
			}

			$flags = join(" ",$flags);
			if(!eregi("X-UM-Flags",$header)) {
				$header .= "\r\nX-UM-Flags: $flags";
			} else {
				$header = preg_replace("/".quotemeta("X-UM-Flags:")."(.*)/i","X-UM-Flags: $flags",$header);
			}

			$msg["header"]  = $header;
			$msg["flags"]	= $flags;

			//print_struc($msg);

			$email = "$header\r\n\r\n$body";

			$this->_save_file($msg["localname"],$email);

			unset($email,$header,$body,$flags,$headerinfo);
		}
		return 1;
	}

	function mail_disconnect() {
		if($this->mail_connected()) {
			if($this->mail_protocol == "imap") {
				if($this->_require_expunge)
					$this->mail_expunge();
				$this->mail_send_command("LOGOUT".$this->CRLF);
				$tmp = $this->mail_get_line();
			} else {
				$this->mail_send_command("QUIT".$this->CRLF);
				$tmp = $this->mail_get_line();
			}
	        fclose($this->mail_connection);
			$this->mail_connection = "";
			//usleep(500);
			return 1;
		} else return 0;
	
	}


	function mail_expunge() {
		if($this->mail_protocol == "imap") {
			$this->mail_send_command("EXPUNGE".$this->CRLF);
			$buffer = $this->mail_get_line();
			if(eregi("^(".$this->_sid." (NO|BAD))",$buffer)) { $this->mail_error_msg = $buffer; return 0; }
			while(!eregi("^(".$this->_sid." OK)",$buffer)) {
				$buffer = $this->mail_get_line();
			}
		}
		return 1;
	}


}
?>