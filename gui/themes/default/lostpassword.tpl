<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <title>{TR_MAIN_INDEX_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="Content-Script-Type" content="text/javascript" />
        <style type="text/css">
        /*<![CDATA[*/
            * {
                margin:                 0;
                padding:                0;
            }

            html, body {
                height:                 100%;
                text-align:             center;
                background-color:       #edeceb;
            }

			body {
                font-family:            arial, serif, sans-serif;
                font-size:				12px;
			}

            a {
                color:                  #ffffff;
            }

			a:hover {
                color:                  rgb(92,186,218);
            }

            #wrapper {
                position:               relative;
                height:                 100%;
            }

            #header {
                height:                 50px;
                background:             url('/themes/default/images/{THEME_COLOR}/login/stripe.png') repeat-x top left;
                border-bottom:          2px solid rgb(102, 102, 102);
				color:					#fff;
            }

            #logo {
                float:                  left;
                text-align:             center;
                top:                    50%;
                display:                table;
                height:                 48px;
                padding-left:           5px;
            }

            #logoInner {
                display:                table-cell;
                padding-top:            10px;
                font-size:              15px;
                font-weight:            bold;
            }

            #logoInner img {
                vertical-align:         middle;
            }

            #copyright {
                float:                  right;
                color:                  #ffffff;
                text-align:             right;
                padding-right:          5px;
                display:                table;
                height:                 48px;
            }

            #copyrightInner {
                display:                table-cell;
                padding-top:            10px;
            }

            #copyrightInner a {
                color:                  #ffffff;
                text-decoration:        none;
            }

            #copyrightInner a:hover {
                color:                  rgb(92,186,218);
                text-decoration:        underline;
            }

            #center, #message {
                position:               absolute;
                top:                    150px;
                width:                  316px;
                left:                   -154px;
                margin-left:            50%;
            }

            #message {
                top:                    52px;
                width:                  400px;
                left:                   -200px;
                border-top:             none;
                min-height:             22px;

            }

			#message p {
				margin-left:50px;
			}


            .icon {
                background-position:    top left;
                background-repeat:      no-repeat;
                padding-left:           20px;
                height:                 16px;
                line-height:            16px;
                display:                -moz-inline-block;
                display:                inline-block;
                margin:                 0 5px;
                float:                  right;
            }

            .success, .error {
                padding:                15px 0 15px 0;
                border:                 1px solid;
                text-align:             left;
                background-repeat:      no-repeat;
                background-position:    10px center;
            }

            .success {
                color:                  rgb(61,122,21);
                border-color:           rgb(61,122,21);
                background-color:       rgb(214,241,179);
                background-image:       url('/themes/default/images/messages/success.png');
            }

            .error {
                color:                  rgb(202,29,17);
                border-color:           rgb(202,29,17);
                background-color:       rgb(253,191,173);
                background-image:       url('/themes/default/images/messages/error.png');
            }

            form {
                float:left;
                background-image:       url('/themes/default/images/{THEME_COLOR}/login/form_box.png');
                background-repeat:      no-repeat;
                background-position:    center top;
            	min-height:             339px;
                height:                 339px;
                width:                  316px;
                text-align:             left;
            }

            form div {
                margin-top:             130px;
                padding:                15px;
            }

            label {
                display:                inline-block;
                width:                  120px;
                color:                  #ffffff;
            }

			input {
				width:					151px;
				height:					26px;
				line-height:			22px;
				border:					2px inset #fff;
				outline-style:          none;
				vertical-align:			middle;
				padding-left:			3px;
				padding-right:			3px;
				font-size:				12px;
				font-family:            arial, serif, sans-serif;
			}

			.buttons input {
				background-color:		#2d2587;
				height:					30px;
				width:					136px;
				color:					#fff;
                -webkit-border-radius:  4px;
                -moz-border-radius:     4px;
				border-style: solid;
                border-radius:          4px;
                /*font-weight:            bold;*/
				cursor:					pointer;
			}

            .buttons input:hover {
                color:                  rgb(92,186,218);
            }

            input[type=text]:hover,
			input[type=text]:focus,
            input[type=password]:hover,
			input[type=password]:focus {
                background-color:       rgb(92,186,218);
            }

            #center ul, #tools ul {
                float:                  left;
                width:                  312px;
                margin-top:             30px;
                list-style:             none;
                list-style-type:        none;
            }

            #center li, #tools li {
                display:                inline;
                margin-left:            10px;
            }

            li a {
                display:                inline-block;
                background-position:    50% 0%;
                color:                  rgb(102, 102, 102);
                background-repeat:      no-repeat;
                width:                  90px;
                line-height:            90px;
            }

            #center p {
                margin-top:             10px;
            }
        /*]]>*/
        </style>
        <!--[if IE 6]>
        <script type="text/javascript" src="/themes/default/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <style type="text/css">
            #message {top: 50px;}
        </style>
        <![endif]-->
	</head>
    <body>
        <div id="wrapper">
            <div id="header">
                <div id="logo">
                    <div id="logoInner">
                        <img src="/themes/default/images/imscp_logo32.png" alt="{productLongName}" />
                        <span>{productLongName}</span>
                    </div>
                </div>
                <div id="copyright">
                    <div id="copyrightInner">
                        <a href="{productLink}" target="blank">{productCopyright}</a>
                    </div>
                </div>
            </div>
            <!-- BDP: page_message -->
            <div id="message" class="{MESSAGE_CLS}">
                <p>{MESSAGE}</p>
            </div>
            <!-- EDP: page_message -->
            <div id="center">
                <form name="login_frm" action="/lostpassword.php" method="post" enctype="application/x-www-form-urlencoded">
                    <div>
						<p>{TR_IMGCAPCODE}</p>
                        <p><label for="capcode">{TR_CAPCODE}:</label>
                            <input type="text" name="capcode" id="capcode" tabindex="1" />
                        </p>
                        <p>
                            <label for="uname">{TR_USERNAME}:</label>
                            <input type="text" name="uname" id="uname" tabindex="2" />
                        </p>
                        <p class="buttons">
                            <!-- BDP: lostpwd_button -->
                            <input type="button" name="lostpwd" value="{TR_CANCEL}" tabindex="4" onclick="location.href='index.php';return false" />
                            &nbsp;&nbsp;
                            <!-- EDP: lostpwd_button -->
                            <input type="submit" name="submit" value="{TR_SEND}" tabindex="3" />
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
