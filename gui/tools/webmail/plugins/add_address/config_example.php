<?php

/**
  * SquirrelMail Add Address Plugin
  * Copyright (c) 1999-2008 The SquirrelMail Project Team
  * Copyright (c) 2008-2009 Paul Lesniewski <paul@squirrelmail.org>,
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage add_address
  *
  */

global $abook_lookup_threshold, $allow_dns_verification,
       $aa_dont_include_identities;


// When searching to see if an address is already in a user's
// address book, if the address is one of the user's outgoing
// email addresses (aliases/identites), should it be skipped
// even if it is not in the address book?
//
// 0 = No.  Include identities too for addition to the address
//     book.
//
// 1 = Yes.  Skip identities; do not offer them for inclusion in
//     the address book
//
// This is a default value that users can override on the
// display options page.
//
$aa_dont_include_identities = 0;



// This plugin allows users to pass email addresses through
// DNS-based verification.  If this creates problems, set
// this to 0 (zero) to disallow this feature.
//
$allow_dns_verification = 1;



// When testing if several addresses are in an address book,
// as of SquirrelMail 1.4.16, this plugin asks the address
// book to look up each address one at a time.  If using
// certain address book backends with large numbers of
// address lookups, this may create unnecessary load on the
// backend.  When this threshold is met (number of addresses
// being looked up at a time), the entire address book contents
// are instead pulled from the backend and cached long enough
// to search for each address in memory instead.
//
// Finding the right threshold is likely to be both system-
// specific and dependent upon the address backend type being
// used and overall system load, as well as the average size
// of user address books.  Please send feedback on what numbers
// worked for you along with details about your system.
//
$abook_lookup_threshold = 50;



