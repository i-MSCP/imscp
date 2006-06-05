<?
########################################################################
# Enable visualization of HTML messages
# *This option afect only incoming messages, the  HTML editor
# for new messages (compose page) is automatically activated 
# when the client's browser support it (IE5 or higher)
########################################################################

$allow_html 			= yes;

########################################################################
# FILTER javascript (and others scripts) from incoming messages
########################################################################
$allow_scripts			= no;


########################################################################
# Block external images.
# If an HTML message have external images, it will be 
# blocked. This feature prevent spam tracking
########################################################################

$block_external_images = no;

########################################################################
# Session timeout for inactivity
########################################################################

$idle_timeout = 10; //minutes

########################################################################
# Session is valid only for the same ip address. If different, will be kicked
########################################################################

$require_same_ip = yes; //minutes

?>
