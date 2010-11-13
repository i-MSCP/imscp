<-- Kilburn al darle eliminar todo no eliminar los tickets 
<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_CLIENT_VIEW_TICKET_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
    </head>

    <body>
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="IspCP logo" />
                <img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="IspCP omega" />
            </div>
        </div>

        <div class="location">
            <div class="location-area icons-left">
                <h1 class="general">{TR_MENU_QUESTIONS_AND_COMMENTS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->                
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="{SUPPORT_SYSTEM_PATH}">{TR_OPEN_TICKETS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

	        <h2 class="support"><span>{TR_VIEW_SUPPORT_TICKET}</span></h2>

			<table>
				<!-- BDP: tickets_list -->
					<thead>
						<tr><th>{TR_TICKET_URGENCY}:</th><th>{URGENCY}</th></tr>
						<tr><th>{TR_TICKET_SUBJECT}: </th><th>{SUBJECT}</th></tr>
					</thead>
					<tbody>
						<!-- BDP: tickets_item -->
							<tr class="line_top"><td>{TR_TICKET_FROM}:</td><td>{FROM}</td></tr>
							<tr><td>{TR_TICKET_DATE}: </td><td>{DATE}</td></tr>
							<tr><td colspan="2">{TICKET_CONTENT}</td></tr>
						<!-- EDP: tickets_item -->
					</tbody>
				<!-- EDP: tickets_list -->
			</table>

			<h2 class="doc">{TR_NEW_TICKET_REPLY}</h2>
			<form name="question_frm" method="post" action="ticket_view.php?ticket_id={ID}">
				<table>
					<tbody>
						<tr>
							<td><textarea name="user_message" cols="80" rows="12"></textarea></td>
						</tr>
					</tbody>
				</table>

				<div class="buttons">
                    <input name="button_reply" type="button" class="button" value="{TR_REPLY}" onclick="return sbmt(document.forms[0],'send_msg');" />
                    <input name="button_action" type="button" class="button" value="{TR_ACTION}" onclick="return sbmt(document.forms[0],'{ACTION}');" />
                </div>
				<input name="uaction" type="hidden" value="" />
				<input name="screenwidth" type="hidden" value="{SCREENWIDTH}" />
				<input name="subject" type="hidden" value="{SUBJECT}" />
				<input name="urgency" type="hidden" value="{URGENCY_ID}" />
			</form>

        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>

    </body>
</html>
