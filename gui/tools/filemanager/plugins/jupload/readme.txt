JUpload Multi-File Upload Web Component

Version 0.86
Copyright (c) 2003-2005 Haller Systemservice

What is JUpload?
 JUpload is a replacement for file upload tags in html.
 It will upload multiple files to the webserver.
 It uses RFC1867 to upload files via POST HTTP request.
 It is also capable fo PUTting files to the webserver, which allows resuming of
 large files.

Features
 Recursive directory upload
 Support for HTTPS (Secure HTTP over SSL)
 Support for proxies.
 Support for public key encryption
 Image preview
 Status display
 File upload resuming (with PUT method)
 Remote controllable (via JavaScript)
 Interaction with your DHTML page
 Plugins
 Customizable look and feel via themepacks (you need SkinLF from javootoo.com)
 ... many more :o)

Requirements
 Java 2 Standard Edition (v1.4 or higher) Plug-In

Author
 Haller Systemservice
 Mike Haller
 info@jupload.biz
 http://jupload.biz/

Contact
 If you have any questions, ask them by mail or in the forum.
 If you have any ideas, improvements etc. send them by mail. :-)
 Please use the word "jupload" in the subject line.

Quick Installation:
 1) unzip jupload.zip into a new directory 
    under your $DOCUMENT_ROOT
 2) start your favorite browser and open examples/jupload-simple-demo.html

Note: the upload will go to the jupload.biz server by default! please do not upload
any files you do not wish to be publicly available nor illegal material. Files will be removed
every 24h.

Problems? Hints!
 a) check that _all_ needed files are in the directory. minimal needed files:
     - jupload.html (can be renamed to index.html)
     - jupload.php (this is the server side part)
     - jupload.jar (this is the main application) *IMPORTANT* :)
 b) check that the upload directory, mentioned in JUpload.php
    does exist and is writable for the webserver.
 c) check that you have implemented the file copy function in
    jupload.php. it is preconfigured to save the files in the
    temp/ directory where you have installed JUpload
    [that is something like copy() or move_uploaded_file()]

If upload still does not work
   - JUpload with POST method (default):
     check php.ini for the following settings:
      file_uploads			(should be ON)
	  upload_tmp_dir 		(defaults to system temp)
	  upload_max_filesize
	  post_max_size
      open_basedir 			(if set, must include upload directory)
     check httpd.conf for the following settings:
	  Limit					(Is POST limited in any way?)
     try to switch off mod_gzip or similar modules

   - JUpload with PUT method (with resuming enabled):
     check php.ini for the following settings:
	  cgi.force_redirect	(if you're using CGI version, esp. with PUT)
	  doc_root				(dto.)
	  cgi.fix_pathinfo=1    (must set this)
     check httpd.conf for the following settings:
	  Limit 				(Is PUT limited in any way?)
	  Script PUT /put.php	(Did you set the correct URL to the handler?)
     try to switch off mod_gzip or similar modules

   - Check your Java Console Log
     Can you see an exception or error message?
     If you see nothing, switch on debug mode in JUpload and try again:
       <PARAM name="debug" value="true">
     You should see, what the server sends back to JUpload.


Advanced Installation
 Your own test certification (basic steps)
   (if you tried this whole thing before, use
     "%JAVA%/bin/keytool -delete -alias jupload"
    to delete your old test key)
    
   1) run

          "%JAVA%/bin/keytool -genkey -alias jupload"

      choose your password. he will then ask a lot of questions, answer them correctly.
      he will now create a key, this will take some time
      he will ask for a passphrase. you can enter a passphrase or hit return to choose
      the same passphrase as your password.
 
      (to automate part of this, use option "-dname")

   2) sign the jar file with your own certificate with

          "%JAVA%/bin/jarsigner jupload.jar jupload

       (Attention: jarsigner does not work with unicode class names)


Credits
 for Support, beta-testing, bug-reports and more
	Yvon Quere, France
	Hans Petter Bjorn, Norway
	Nick, USA
	Helmet, Netherlands
	and many more (see forum) :-)

 thanks go to the translation work of the following people:
	Sandro Paganotti, Italy [it_IT]
	Ricardo Hermosilla, Chile [es_CL]
	Ruud Kamphuis, Netherlands [nl_NL]
	H4xx0r, Germany [ru_RU]
	Thomas Trötscher [no_NO]
	Roberto C. Ibarra Rabadán, Mexico [es_MX]
	Jean Delvare, France [fr_FR]
	Waldyr Alves, Brasil [pt_BR]

About
   JUpload started as a proof-of-concept prototype for uploading multiple
   files (RFC1867) with the HTTP POST method using java applet technology
   and especially the jarsigner utility. As it turned out that JUpload
   was a much-needed tool, development started and implementation proresses
   as new features have to be coded and bugfixes be integrated.

   Mike Haller
   info@haller-systemservice.net
   http://www.haller-systemservice.net/

   If you have any questions, ask them by mail.
   If you have any ideas, improvements etc. send them by mail. :-)
   Please use the word "jupload" in the subject line.

/* CVS Information
 * Author: $Author: mhaller $
 * Date: $Date: 2005/06/13 14:24:45 $
 * Id: $Id: readme.txt,v 1.22 2005/06/13 14:24:45 mhaller Exp $
 * Revision: $Revision: 1.22 $
 */
 