#! /usr/bin/perl

# This script takes an Unicode character map as input and outputs
# conversion code for SquirrelMail charset support.

# This code is placed in the Public Domain. Written by Gustav Foseid,
# gustavf@squirrelmail.org

$min = 160;

while (<>) {
  chop;

  unless (/^\#/) {

    ($orig_text, $unicode_text, $dummy, $name) = split /\t/;
	
    # oct does not only do what it's name suggest. If a string starts 
    # with 0x it is interpreted as a hexadecimal value.
    $orig = oct $orig_text;
    $unicode = oct $unicode_text;
	
    if ($orig >= $min) {
      print "            // $name\n";
      printf ('            $string = str_replace("\%o", "&#%d", $string);'."\n",
	      $orig, $unicode);
    }
  } 
}
