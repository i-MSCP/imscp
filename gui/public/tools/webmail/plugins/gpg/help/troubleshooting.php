<?php
/**
 * Troubleshooting page.
 *
 * @todo Convert most of this file to HTML, and only use
 *       parts of in inside PHP tags
 *
 * $Id$
 */

echo '<h2>'. _("Troubleshooting") . '</h2>';
echo '<P>'
  .    "Forgive us if this isn't complete, we've made every attempt to list
        the most common problems and solutions."
  . '</P>';
echo '<P>' .
        "This section is organized with the error you see on the screen at the
        beginning of each TroubleShooting section, and the workaround or other
        information indented under the applicable error message(s).";
echo '<P>' .
        "We also welcome additions to this section.";
echo '<P>'
  . "I installed the Plugin, and it doesn't work!";
echo '<UL>';
                echo  <<<TILLEND
        You will need to provide more information than this.
                <p>
                First, check the "Tested On:" section, below, and see
                if your configuration looks like it should work (focus on
                PHP Version, SM version).
                <p>
                Often, installation problems have been resolved by upgrading SM,
                PHP, Apache, etc.  There are good security and functionality reasons
                to upgrade to more modern version in most cases as well.
                <p>
                Next, take a look at the specific error messages the plugin is
                giving you.  The development team has made an effort to provide error
                messages that can assist you in diagnosing your problem.
                <p>
                If you still can't figure it out, see the contact information below.
                If you write to the development team about a problem, please include
                as much information about your configuration and the exact error you
                are receiving as possible.
            <p>
TILLEND;
echo '</UL>';
echo 'PHP file_uploads variable:';
echo '<UL>';
echo <<<TILLEND
                Make sure that your file_uploads variable is set to 'On'
                in php.ini. This is set to 'Off' by default (for security)
                in some distributions.
<p>
                The line should read:<br>
                file_uploads = On<br>
<p>
                Failure to do this will, at best, keep you from uploading a key file.
                At worst, no data will be submitted on POST from the form, due to
                a known bug in PHP:
                 <p>
                <A HREF="http://bugs.php.net/bug.php?id=17958">http://bugs.php.net/bug.php?id=17958</A>
TILLEND;
echo '</UL>';
echo '<p>PHP allow_url_fopen variable</p>';
echo '<UL>';
echo <<<TILLEND
        Unable to connect to the keyserver you specified.<br>
        Please try a different keyserver or contact your system administrator.<br>
                If you can't look up keys on any public keyservers, check your firewall rules.
               <p>
                If the firewall isn't blocking traffic, check the value of the variable
                allow_url_fopen in php.ini.  This variable must be set to 'On' for
                keyserver lookup to work.
                 <p>
TILLEND;
echo '</UL>';
echo '<p>Compose problems/ warning messages:</p>';
echo '<UL>';
echo <<<TILLEND
                If you do not have gpg installed correctly, including correct ownership
                and permissions on your &lt;Squirrelmail&gt;/data/&lt;account&gt;.gpg/ directory and
                all files contained within, when you go to encrypt your message you will
                be shown the warning messages and then returned to the compose page
                WITHOUT encrypting your email. Fix the problems and when the warnings go
                away your email can be encrypted.
TILLEND;
echo '</UL>';
echo '<p>Replying to a decrypted messages shows a blank or incorrect body</p>';
echo '<UL>';
echo <<<TILLEND
                When a user has the Compose in New Window preference set, and decrypts a
                message using the plugin, it is possible that the body during a reply can
                appear blank.  This can happen if a user decrypts a message, then clicks
                Compose and then chooses to reply to the decrypted message.  This particular
                behavior will be altered in a later release.  For the time being, if you wish
                to reply to decrypted messages, click reply before composing any new messages.
TILLEND;
echo '</UL>';
        echo '<p>gpg: Warning: using insecure memory! </p>';
echo '<UL>';
echo <<<TILLEND
                The "using insecure memory" warning is a gpg issue, not a plugin problem.
                When gpg is installed incorrectly, it can use insecure memory (a non-locked
                page file) and there exists a possibility that the memory block allocated by
                gpg can be written to the disk on swap, potentially leaving keys,
                passphrase, or plaintext in an insecure swap file.  On Unix, making the gpg
                binary setuid root may remove this warning.  Some Linux distributions
                distribute gpg in a non-setuid root form, probably mistakenly thinking that
                it will be more secure to not have the gpg binary be setuid root.
<p>
                Reference: <A HREF="http://www.gnupg.org/documentation/faqs.html#q6.1">http://www.gnupg.org/documentation/faqs.html#q6.1</A>
<p>
TILLEND;
echo '</UL>';
echo '<p>';
echo <<<TILLEND
    gpg: protection algorithm 1 (IDEA) is not supported<br>
        gpg: the IDEA cipher plugin is not present<br>
        gpg: please see <A HREF="http://www.gnupg.org/why-not-idea.html">http://www.gnupg.org/why-not-idea.html</A> for more information<br>
        gpg: no default secret key: unknown cipher algorithm<br>
        gpg: [stdin]: clearsign failed: unknown cipher algorithm<br>
</p><UL>
                You will get the above sequence of errors if you try to use an RSA secret
                key as a signing key, or if you try to decrypt something that was encrypted
                with PGP 2.x.
                <p>
                This can be remedied by either loading the IDEA compatibility plugin for gpg,
                or by waiting for the GPG Squirrelmail Plugin team to get around to warping
                the command we send gpg to use the CAST/MD5 or SHA1 algorithm for the hash.
                <p>
                We'll get to it soon, I promise.
</UL>
        Fatal error: Call to undefined function: check_php_version()
<UL>
                The GPG plugin requires a function from Squirrelmail called check_php_version.
                This function was added to the SM core code in version 1.2.9. If you are
                receiving this error, you are probably using a version of SM prior to 1.2.9
                You have two options:
<P>
                Upgrade Squirrelmail to a more recent version.
<UL>
                        This is the preferred option because all versions of Squirrelmail
                        prior to 1.2.11 have a remotely exploitable cross site scripting
                        security vulnerability.
</UL>
                Hack the check_php_version() function into your version of Squirrelmail.
<UL>
                        The check_php_version function is very small.
                        You could patch it into your version of Squirrelmail.
                        Put the following in your src/load_prefs.php file
<p><pre>
TILLEND;

// call this out to keep it from generating a php parse error
echo "/* returns true if current php version is at minimum a.b.c */\n"
.'function check_php_version ($a = "0", $b = "0", $c = "0")'."\n"
."     {\n"
.'          $SQ_PHP_VERSION=phpversion(); /* set your PHP version here */'."\n"
."\n"
.'          return $SQ_PHP_VERSION &gt;= ($a.$b.$c);'."\n"
."       }\n";

echo <<<TILLEND
</pre><p></UL></UL>
    About Decryption
<ul>
    <p>
        Once you have uploaded a secret key or keyring, the decryption part of the
        plugin will be activated.  The plugin will try to automatically determine
        when a message contains OpenPGP/GPG encrypted content.  When the plugin
        detects a message that appears to have encrypted content, the
        'Decrypt Message Now' button will be displayed.  Because of the complexity
        of this feature, it is possible that the plugin may not always correctly
        identify a message that contains encrypted content.  Please report any
        unusual behavior, so that we can work with you to resolve your issue and
        patch the plugin.
</ul>
    About Signing
<ul>
    <p>
        Once you have uploaded a secret key or keyring, you need to select a signing
        key. Once you have selected a signing key, the signing portion of the plugin
        will become activated.  The 'Encrypt & Sign Now' and 'Sign on Send' buttons
        will display in the Compose window of Squirrelmail.  You should now be able
        to sign mail with your signing key.
</ul>
        gpg: secret key not imported (use --allow-secret-key-import to allow for it)
<UL>
                If you are using gpg v 1.0.5 or 1.0.6 you may see this message
                upon attempting to upload a private key/keyring.
     <p>
                Thanks to Derek Battams for reporting this issue.
     <p>
                Upgrading to a newer version of gpg will resolve this problem.
     <p>
                The development team has applied a patch for this issue, so
                hopefully no one will ever see this error.<br>
                Full details are available at:<br>
                <A HREF="http://www.braverock.com/bugzilla/show_bug.cgi?id=16">http://www.braverock.com/bugzilla/show_bug.cgi?id=16</A>
</UL>
        pgp/mime support
<UL>
                The plugin currently has limited support for messages encoded
                with mime type 'application/pgp'
    <p>
                The plugin will correctly identify and verify signatures inside
                mime parts and decrypt messages in mime attachments.
    <p>
                The support still isn't perfect.  I can't figure out how to get
                Squirrelmail 1.2.11 to display the mime attachments in the browser window.
                That isn't precisely a plugin issue, I'll have to investigate how SM
                handles mime attachments, and figure out if there is any why to get SM 1.2.11
                to display the mime parts.
    <p>
                Mime attachments display in SM 1.4, but not in SM 1.2.11.  I think this is
                just the way SM is, and we aren't going to be able to affect that.
    <p>
                The development team will work to resolve this issue in a
                future release.  Any information on anomalous behavior, or
                suggested patches, would be appreciated.<br>
                Please add information to the bug report at:<br>
                <A HREF="http://www.braverock.com/bugzilla/show_bug.cgi?id=20">http://www.braverock.com/bugzilla/show_bug.cgi?id=20</A>
</UL>
        Encrypt fails on SM 1.4.0 &amp; IE 6 or Netscape 7
<UL>
                Javascript Error: "The object does not support that property or method"
                          "Error: Error:'this.form.action' is null or not an object"
                These errors are caused by a bug in the released version
                of SM 1.4.0.  SM 1.4.0 uses a reserved word 'action' to name
                a hidden field.  This interferes with the plugin's use
                of the Javascript this.form.action to redirect the submit
                when the user presses 'Encrypt Now'.
<p>
                This error will only occur on browsers that do strict form checking
                of the DOM before executing the this.form.action command,
                principally some versions of IE 6 and Netscape 7.
<p>
                The Squirrelmail core team is aware of this issue, and expects to
                apply the patch to SM 1.4.1.
<p>
                The only solution to this issue in SM 1.4.0 is to patch
                compose.php (and optionally read_body.php) to change the
                hidden field 'action' to a name that is not a reserved word.
<p>
                Full details and the patch are available at:<br>
                <A HREF="http://www.braverock.com/bugzilla/show_bug.cgi?id=24">http://www.braverock.com/bugzilla/show_bug.cgi?id=24</A>
</UL>
        Signing Multiple attachments only shows the first
<UL>
                These errors are caused by a bug in the released versions
                of SM up to and including SM 1.4.2.
<p>
                Full details and the patch are available at:<br>
                <A HREF="http://www.braverock.com/bugzilla/show_bug.cgi?id=99">http://www.braverock.com/bugzilla/show_bug.cgi?id=99</A>
</UL>
<p>
        GPG Plugin Error:No body text received from Compose page.
<UL>
                The error you are seeing indicates that the signing javascript
                was unable to pull the body text from the Compose page and was added
                for just this kind of circumstance.
<p>
                Users who experienced problems similar to this one with Signing
                from IE 6 have been able to resolve it by updating to the newest
                Microsoft IE Service Pack and JScript engine, which should be
                available from Windows Update.
<p>
                Updating IE is probably the best we can offer right now. If you update
                IE and still experience this problem, please contact us, as several
                members of the development team use IE as thier primary environment,
                and we would be happy to help you resolve your issue.
<p>
                If there is an IE JScript wizard out there who can figure out
                why this is flaky, we'd love to hear about it.
<p>
</UL>
        gpg: Oops: keyid_from_fingerprint: no pubkey
<UL>
                From and old post to the gpg devel list:
                When you delete a key with ownertrust set it does not disappear
                from the trustdb.  I mentioned this one a few months ago and I
                think it was concluded to be a feature and not a bug, though I
                still think that if I delete a key there should be no remnants of
                that key left behind.  The more significant problem is if there is
                an ultimately trusted key that gets deleted, then gpg will complain
                constantly:<br>
                gpg: Oops: keyid_from_fingerprint: no pubkey<br>
                and whenever --update-trustdb or --check-trustdb is run:<br>
                gpg: public key of ultimately trusted key 00000000 not found<br>
<p>
                We're working on a resolution/work around to this problem.
                Full details at:<br>
                <A HREF="http://www.braverock.com/bugzilla/show_bug.cgi?id=67">http://www.braverock.com/bugzilla/show_bug.cgi?id=67</A>
</UL>
<p>
        attachment of type application/pgp
<UL>
                The mime type application/pgp is deprecated, and we do not
                currently support it.  Some browsers (notably Opera) will
                upload a file with a .asc extension to SM as a
                application/pgp binary attachment.  A bug report has been
                filed with Opera.
</UL>
<p>
        Google Toolbar for Microsoft Internet Explorer on Windows
<ul>
                If you are using the Google Toolbar on MSIE, there will be a
                short (10s) delay before the "Enter Passphrase" window appears.
                Clicking on "Blocking popups" in the toolbar will toggle it to
                "Site popups allowed." However, the delay will still be present.
                This is appears to be normal for the Google Toolbar and not a bug
                in the GPG Plugin.
<p>
                ("normal" as in it does the same thing on ANY other site that uses popups.)
</ul>
TILLEND;

/**
 * $Log: troubleshooting.php,v $
 * Revision 1.7  2004/03/17 15:24:49  brian
 * - updated several sections
 * - added 'About' sections for Signing/Decryption
 *
 */
?>