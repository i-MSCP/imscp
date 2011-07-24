<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/india/google_login_adsense.template.php begin -->
<?php if ($net2ftp_settings["show_google_ads"] == "yes") { 
if     ($net2ftp_globals["language"] == "de") { $google_cpa_choice = "CAAQ-arsiwIaCHOQv-ERh6OOKNOV5HQ"; }
elseif ($net2ftp_globals["language"] == "fr") { $google_cpa_choice = "CAAQkePuiwIaCO3UAjw69sk6KNGZ5HQ"; }
elseif ($net2ftp_globals["language"] == "it") { $google_cpa_choice = "CAAQ7bnviwIaCPOW4PxY3WhwKKua5HQ"; }
elseif ($net2ftp_globals["language"] == "ja") { $google_cpa_choice = "CAAQnfbyiwIaCNKfaUtvR77WKI3vpY4B"; }
elseif ($net2ftp_globals["language"] == "nl") { $google_cpa_choice = "CAAQ6fWwkwIaCMOg2EQx53OaKLGd5HQ"; }
elseif ($net2ftp_globals["language"] == "pt") { $google_cpa_choice = "CAAQsf6ZjAIaCHn6Z3b50_O2KK3_5HQ"; }
elseif ($net2ftp_globals["language"] == "se") { $google_cpa_choice = "CAAQqd6ujAIaCJMeCxvcJoYFKKH2sI4B"; }
elseif ($net2ftp_globals["language"] == "tc") { $google_cpa_choice = "CAAQnYWvjAIaCL6aFHRetkuqKPm0i4gB"; }
elseif ($net2ftp_globals["language"] == "zh") { $google_cpa_choice = "CAAQueuujAIaCHec1tjtOZDMKNX3sI4B"; }
else                                          { $google_cpa_choice = "CAAQ7c2WhAIaCETpCEPuYoTVKPmNxXQ"; }
?>
<script type="text/javascript"><!--
google_ad_client = "pub-8420366685399799";
google_ad_width = 110;
google_ad_height = 32;
google_ad_format = "110x32_as_rimg";
google_cpa_choice = "<?php echo $google_cpa_choice; ?>";
google_ad_channel = "4022212193";
//--></script>
<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
<?php } // end if ?>
<!-- Template /skins/india/google_login_adsense.template.php end -->
