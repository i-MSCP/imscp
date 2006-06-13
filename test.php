<?
function vhcs_email_check($email, $num) {
  // RegEx begin
  
  $nonascii      = "\x80-\xff"; # Non-ASCII-Chars are not allowed

  $nqtext        = "[^\\\\$nonascii\015\012\"]";
  $qchar         = "\\\\[^$nonascii]";

  $normuser      = '[a-zA-Z0-9][a-zA-Z0-9_.-]*';
  $quotedstring  = "\"(?:$nqtext|$qchar)+\"";
  $user_part     = "(?:$normuser|$quotedstring)";

  $dom_mainpart  = '[a-zA-Z0-9][a-zA-Z0-9._-]*\\.';
  $dom_subpart   = '(?:[a-zA-Z0-9][a-zA-Z0-9._-]*\\.)*';
  $dom_tldpart   = '[a-zA-Z]{2,5}';
  $domain_part   = "$dom_subpart$dom_mainpart$dom_tldpart";

  $regex         = "$user_part\@$domain_part";
  // RegEx end
  
  if (!preg_match("/^$regex$/",$email)) return 1;
  	
  if (strlen($email) > $num) return 1;
  	
  return 0;
  
}

echo "E-Mail TEsten !";

$fehler = vhcs_email_check("malte@g-house.de, 50);

echo $fehler;

?>
