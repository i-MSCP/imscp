<meta http-equiv="Content-Type" content="text/plain; charset=ansi">
<!--
  * $Id$
-->

Readme file for Squirrelmail GPG Plugin code

Last Minute Stuff
    The plugin has evolved quite significantly since it's last incarnation.

    This README file indicates that you are running GPG Plugin v 2.1.

    This version contains security fixes to prevent possible command injection
    attacks by local authenticated users against the webserver user.
    The possible impact of these attacks would have been fairly narrow in a
    properly configured webserver environment.  The inclusion of proc_open
    functionality also significantly improves overall security by allowing us to
    use pipes to communicate with the gpg binary.

    A major new feature is our use of a new method of interaction with
    the GPG binary, using PHP's proc_open function.  This function does
    not exist in PHP versions before 4.3.  While we have worked to include
    all functionality whether or not proc_open is enabled, some features
    simply do not work without it.  We are working to hide these features
    when they are not enabled, but users should be aware that this is
    still in development.  We welcome feedback and bug reports of all
    varieties in our GPG Plugin Bugzilla at:
    http://www.braverock.com/bugzilla/

    This version also contains experimental code for passphrase caching.
    It has known issues when initially caching on decryption, and only
    allows one passphrase to be cached.  It is recommendeded that you
    leave this feature disabled unless you know what you are doing.

    Many things were in flux as we added proc_open support and move to
    using a central object for GPG calls.  If you are having problems, you
    should first update to the latest CVS code or daily build tarball. If
    reporting problems with the development code, you should also note what day
    you have code from, so we can easily tell whether your problem has already
    been addressed.  Details below for upgrading to current code.

    GPG Plugin v. 2.1 contains complete support for translation of the plugin.
    The Plugin is shipping with complete translations for Lithuanian, Italian,
    German, French, Spanish, Dutch, and Brazilian Portuguese.
    Our sincere thanks to the translators for the work involved in
    translating something as complex as user documentation for encryption.
    If you are reading this and your native language is not English, we
    would love to integrate support for your language into the next release.
    The gpg.pot file is in the locales directory.  Please contact the
    GPG Plugin development team with any questions, and we will be happy to
    help you with the format and testing.

    We have changed the SM requirement to 1.4.2. Some of the newest
    functionality for handling detached signatures and decrypting attachments
    depends on changes to the Message class and Deliver class that are dependent
    on SM 1.4.2.  Some of the most complete MIME functionality only works with
    SM 1.4.3a or higher.

    There was a bug in SM's handling of multi-part MIME messages, which
    affected our ability to do attachments with detached signatures.
    The bug has been corrected in the SM core CVS, as well as the released
    Squirrelmail 1.4.3a but if you want to use this functionality in previous versions,
    check out:
    http://www.braverock.com/bugzilla/show_bug.cgi?id=99
    for an updated version of the Deliver class that resolves this bug
    (patch available there as well)

    The GPG Plugin team would like to thank the Cryptorights Foundation for their
    generous support. CRF has supported the development of the GPG Plugin,
    and will be including the plugin in their HighFire Product.
    Please support a worthy cause: http://www.cryptorights.org/

Introduction

    The GPG Plugin for Squirrelmail exists to allow you to do all common
    OpenPGP functions via a common user interface inside of the popular
    Squirrelmail web mail program.  This includes, but is not limited to:
    encryption, decryption, key management, key generation, keyserver
    functions, and signature verification.  The plugin was written because
    for quite a long time, OpenPGP functionality was one of the most requested
    new features of Squirrelmail.  Somebody had to do it.  We did it. We hope
    that you find it useful.

    The GPG Plugin for Squirrelmail is intended for most general-purpose
    'convenience' encryption needs.  If you are an average user of a web-mail
    system, or if your system is maintained on a secured server that is not
    open to the public Internet, then the GPG Plugin can probably be of use
    to you in common encryption and decryption tasks. If you have truly
    stringent needs for encryption (like keeping governments or security
    experts out of your data), the GPG Plugin can still be useful, but it
    does not take the place of careful off-line key management.

    The authors of the GPG Plugin have made every attempt to create secure
    software, and will respond quickly to any suggestions for making it more
    secure.  This software is offered without any warranty, express or implied,
    to the fullest extent permitted by law.  We offer the GPG Plugin in the
    hope that it will be useful: no more, and no less.

New Features

    proc_open Features (requires PHP 4.3.x or higher, sorry)
        Key Signing
        Key Revocation
        Change Passphrase
        Subkeys (creation, deletion, signing)
        Key Attributes

    The usual Bug fixes and minor Enhancements
        Details available at http://www.braverock.com/bugzilla/
        (over 60 bugs resolved between v 2.0 and v 2.1)

Installation

    See the file INSTALL.txt

    Upgrade instructions are also in the INSTALL.txt file.

Troubleshooting

    Forgive us if this isn't complete, we've made every attempt to list
    the most common problems and solutions.

    Most of the Troubleshooting information may be found in the section
    called 'Troubleshooting' in the GPG Plugin Help from within the Squirrelmail
    interface.

    The Troubleshooting section in the documentation is organized with the error
    you see on the screen at the beginning of each TroubleShooting section, and
    the workaround or other information indented under the applicable error
    message(s).

    We also welcome additions to this section.

    I installed the Plugin, and it doesn't work!

        You will need to provide more information than this.

        First, check the "Tested On:" section, below, and see
        if your configuration looks like it should work (focus on
        PHP version, SM version, GPG version).

        Often, installation problems have been resolved by upgrading SM,
        PHP, Apache, GPG, etc.  There are good security and functionality
        reasons to upgrade to more modern version in most cases as well.

        Next, take a look at the specific error messages the plugin is
        giving you.  The development team has made an effort to provide error
        messages that can assist you in diagnosing your problem.

        If you still can't figure it out, see the contact information below.
        If you write to the development team about a problem, please include
        as much information about your configuration and the exact error you
        are receiving as possible.


About Keyservers, firewalls, and LDAP

        The GPG Plugin uses an HTTP interface to retrieve a list of keys
        for import to the user's keyring.  Most of the Keyservers that offer an
        HTTP interface put it on port 11371.  If your Squirrelmail server is
        behind a firewall or on an ipchains/iptables machine that blocks outgoing
        connections, you will need to open up connections from the server the gpg
        plugin runs on to the keyservers that are defined in your config files.

        There are only three interfaces that we know of to a keyserver, the HTTP
        interface currently used by the gpg plugin, the HKP interface, and an
        LDAP interface.  We believe that it would be nice to support the LDAP
        and HKP interfaces, and would welcome any code contributions that
        implement such an interface for key lookup.  The HKP and LDAP interfaces
        will probably be supported via the --search-keys functionality,
        although this is an interactive interface, and may not be suitable for
        use in the plugin.

About Decryption

    Once you have uploaded a secret key or keyring, the decryption part of the
    plugin will be activated.  The plugin will try to automatically determine
    when a message contains OpenPGP/GPG encrypted content.  When the plugin
    detects a message that appears to have encrypted content, the
    'Decrypt Message Now' button will be displayed.  Because of the complexity
    of this feature, it is possible that the plugin may not always correctly
    identify a message that contains encrypted content.  Please report any
    unusual behavior, so that we can work with you to resolve your issue and
    patch the plugin.

About Signing

    Once you have uploaded a secret key or keyring, you need to select a signing
    key. Once you have selected a signing key, the signing portion of the plugin
    will become activated.  The 'Encrypt & Sign Now' and 'Sign on Send' buttons
    will display in the Compose window of Squirrelmail.  You should now be able
    to sign mail with your signing key.

About Keyring Ownership

    The GPG Plugin will create keyrings in the proper prefs directories.
    If you modify these keyrings, make sure that they are still owned and
    readable only by the webserver user.  The gpg binary will fail to work
    with keyrings owned or usable by another user.


	Once you have uploaded a secret key or keyring, you need to select a signing
	key. Once you have selected a signing key, the signing portion of the plugin
	will become activated.  The 'Encrypt & Sign Now' and 'Sign on Send' buttons
	will display in the Compose window of Squirrelmail.  You should now be able
	to sign mail with your signing key.  Once this has been done, seperate
	signing keys can be selected for each squirrelmail identity.


CVS (source code) Access:

    Web browser access to the repository is available at:

    http://www.braverock.com/gpg/cvs/

    if you want the daily snapshot of our development code, it is rebuilt in
    the wee hours of the morning and is available at:

    http://www.braverock.com/gpg/dailybuild/

    I've also configured anonymous cvs access to the cvs repository.

    You will need to set the CVS_RSH environment variable to 'ssh'.

    On unix, in  bash, you can do this by adding the following commands
    to your .bashrc or .profile

    CVS_RSH=ssh
    export CVS_RSH

    In t/csh, you would use 'setenv CVS_RSH=ssh'

    Then, you can checkout the code using the following command
    from the command line:

    cvs -d :ext:anoncvs@braverock.com:/cvs co gpg

    passwd:anoncvs

    Mac/WinCVS configuration should be similar.  Select SSH as the
    server type, instead of pserver.

Reporting Bugs

    We want to hear about your Bug Reports, Enhancement Requests, and Patches.

    Please submit bug reports at:
    http://www.braverock.com/bugzilla/index.cgi

    Please search the bug list for your bug before posting,
    and add additional detail as appropriate.

    Enhancements should be marked with a severity of 'enhancement'.

    Please read the Bug writing guidelines at:
    http://www.mozilla.org/quality/bug-writing-guidelines.html
    for assistance in how to write a bug report that
    will get the results you want.

Development Team

    List:       gpg@braverock.com
            (subscribe by sending a message to
             gpg-request@braverock.com with body 'subscribe')

    Team Lead:  Brian Peterson
            brian@braverock.com

    Coding/Testing: Aaron van Meerten (ke)
            Walter Torres
            Tyler Allison
            Joel Mawhorter
            Brad Donison
            Joshua Vermette
            Kipp Spanbauer
            Ryan

    Design/Coding
    Assistance: John Nanninga (Design & Testing)
            Glenn Powers  (Testing)
            Vinay
            Julian Dobson
            Greg Winston


<!--
  /**
   $Log: README.txt,v $
   Revision 1.72  2006/08/18 21:16:48  brian
   - update README text

   Revision 1.71  2005/11/11 17:57:09  ke
   - added notes about signing key and identities
   - simplified squirremail version requirements

   Revision 1.70  2005/06/09 14:58:57  brian
   - updated to reflect proc_open features and v 2.1 new features

   Revision 1.69  2005/06/09 14:25:25  ke
   - added note about experimental nature of passphrase caching

   Revision 1.68  2004/08/22 00:44:50  ke
   -commit to test bugzilla
   Bug 6

   Revision 1.67  2004/07/09 21:01:25  ke
   -added description of proc_open development status
   -updated tense of Deliver.class bug description
   Bug 199

   Revision 1.66  2004/07/09 20:57:31  brian
   - updated README file to reflect GPG Plugin v2.1 dev version
   Bug 199

   Revision 1.65  2004/03/17 15:55:02  brian
   - added 'Introduction'
   - updated several sections
   - spell check

   Revision 1.64  2004/02/26 19:52:29  ke
   -testing bugzilla integration
   bug 7

   Revision 1.63  2004/02/26 19:43:08  ke
   -testing bugzilla integration
   bug 7

   Revision 1.62  2004/01/13 01:20:29  brian
   - formatting changes for better rendering as HTML

   Revision 1.61  2004/01/11 03:59:14  brian
   - updated platforms list

   Revision 1.60  2004/01/08 23:23:50  brian
   - added Dutch

   Revision 1.59  2004/01/08 22:50:08  brian
   - added Spanish to the intro

   Revision 1.58  2004/01/06 19:26:33  brian
   - spell check
   - update to reflect current

   Revision 1.57  2003/12/03 17:24:50  brian
   - added to New Features section
   - removed signing section as no longer relevant
   - updated text throughout
   - spell check

   Revision 1.56  2003/12/01 19:18:52  ke
   - spelling correction
   - added section on decrypting caching logic issue

   Revision 1.55  2003/11/22 15:30:49  brian
   - Moved all the INSTALL sections to file INSTALL
   - Bug 70

   Revision 1.54  2003/11/21 22:39:48  ke
   - added text for new configuration options
   - fixed small typo

   Revision 1.53  2003/10/31 14:00:16  brian
   - Updates prior to Release Candidate
   - reviewed new features to get things
     closer to reality, still work to do here.

   Revision 1.52  2003/09/30 12:03:15  brian
   - updated to reflect current
   - paid particular attention to the 'New Features' section
   - added link to the dailybuild to README

   Revision 1.51  2003/06/17 00:11:23  brian
   - update to reflect current
   - removed mcrypt section, as it is no longer relevant

   Revision 1.50  2003/06/12 21:07:35  brian
   - minor updates

   Revision 1.49  2003/05/09 15:29:28  brian
   - Changed "last minute" section to "New Information"
   - added all the new features since v1.1 to 'New information"
   - Added upgrade instructions to "Installation"
   - added additional troubleshooting sections
   - added new options to configuration section

   Revision 1.48  2003/05/09 15:23:04  brian
   - Changed "last minute" section to "New Information"
   - added all the new features since v1.1 to 'New information"
   - Added upgrade instructions to "Installation"
   - added additional troubleshooting sections

   Revision 1.47  2003/05/02 15:35:59  Brian
   - updated to reflect current

   Revision 1.46  2003/04/26 19:22:04  Brian
   - added maxfilesize to configuration options section of README
   - Bug 31

   Revision 1.45  2003/04/14 16:10:10  Brian
   - more troubleshooting, and spell check

   Revision 1.44  2003/04/08 16:04:55  Brian
   - added more detail on mcrypt installation

   Revision 1.43  2003/04/08 04:50:30  Brian
   - added report on reserved word bug to Troubleshooting section
   - Bug 24

   Revision 1.42  2003/04/04 16:00:26  Brian
   - add link to gpg mini-howto

   Revision 1.41  2003/04/04 00:11:00  Brian
   - last minute updates

   Revision 1.40  2003/04/02 22:56:40  Brian
   - Spell Check
   - added more detail to troubleshooting and decryption sections
   - Bug 18

   Revision 1.39  2003/04/02 16:35:59  Brian
   - added troubleshooting sections
   - added more information about key upload and decryption
   - updated last minute section

   Revision 1.38  2003/04/01 06:40:18  Brian
   - added information about decryption and sm 1.4 compatibility

   Revision 1.21  2003/03/20 10:50:40  Brian
   - Added Bug Report Section

   Revision 1.20  2003/03/14 16:01:20  Brian
   - added Joel to team list

   Revision 1.19  2003/03/14 14:50:39  Brian
   - spell check and add Troubleshooting section for check_php_version

   Revision 1.18  2003/03/14 14:04:17  Brian
   - updated signing section

   Revision 1.17  2003/03/13 04:03:53  Brian
   - added troubleshooting option for IDEA

   Revision 1.16  2003/03/12 19:49:01  Tyler
   - rewrote some of the key signing areas and added a 'What you need to know'
   - section where we can put the status of the plugin,
   - like what is working and what isn't

   Revision 1.15  2003/03/12 16:47:42  Brian
   - updates to clarify things before release

   Revision 1.14  2003/03/12 04:01:06  Tyler
   - removed the part about editing the hard coded signing key

   Revision 1.13  2003/03/11 18:28:19  Tyler
   - Added section on how to setup gpg to allow for the use of the sign_message functions

   Revision 1.12  2003/03/11 06:44:11  Brian
   - troubleshooting section on "using insecure memory"

   Revision 1.11  2003/03/11 06:38:10  Brian
   - troubleshooting section on "using insecure memory"

   Revision 1.10  2003/03/10 18:28:13  Tyler
   - Added 'Compose problems/warning messages' section

   Revision 1.9  2003/03/10 03:59:02  Tyler
   - test Tyler

   Revision 1.8  2003/03/07 17:10:03  Brian
   - Added TroubleShooting and CVS repository sections

   Revision 1.7  2003/03/05 14:55:30  Brian
   - Public Release Notes and Credits

   Revision 1.6  2003/02/19 23:53:38  Brian
   - minor wording changes

   Revision 1.5  2003/01/07 13:10:20  Brian
   - Several additions, including more information on the config files, and spell check.

   Revision 1.4  2003/01/03 22:31:47  Brian
   - Added more information on configuration and installation, and spell check.

   Revision 1.3  2002/12/09 15:22:22  Brian
   - updated content-type and Id and Log tags

 */
-->