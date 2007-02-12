#! /usr/bin/perl -w
# $Id: make-release.pl,v 1.4 2005/01/24 18:28:07 itools Exp $
# Script for creating a distribution archive.

# Original version created by Mihai Bazon, http://dynarch.com/mishoo
# NO WARRANTIES WHATSOEVER.  READ GNU LGPL.

# This file requires HTML::Mason; this module is used for automatic
# substitution of the version/release number as well as for selection of the
# changelog (at least in the file release-notes.html).  It might not work
# without HTML::Mason.

# Load Modules
# NOTE: when we don't import functions it's so we'll use fully qualified package
# names and make it excessively clear which modules functions are being called from.
use strict;
# use diagnostics;
use HTML::Mason ();
use File::Copy ();
use File::Path ();
use Cwd ();
use FindBin ();
use XML::Parser ();
use Data::Dumper ();
use Archive::Zip ();

# ANSI codes aren't supported on windows, install this module to support them:
# C:\>ppm install http://www.bribes.org/perl/ppm/Win32-Console-ANSI.ppd
# we use 'eval require' instead of 'use' so it don't die if module isn't available.
eval { require 'Win32/Console/ANSI.pm'; };

# Globals
my $SPEAK_VERBOSITY  = 1;              # Controls which message level that &speak outputs
my $SCRIPT_DIR       = $FindBin::Bin;
my $BUILD_DIR = "$SCRIPT_DIR/builds/"; # this is where zip files are created
my $BASEDIR;                           # defined in main - dir under $BUILD_DIR where files are copied, zipped, then removed.
my $REMOVE_BASEDIR_AFTER_ZIPPING = 0;

# run program
&main();

# ------------------------------------------------------------------------
# Function    : main
# Description :
# Usage       :
# ------------------------------------------------------------------------

sub main {

  if (!-d $BUILD_DIR) { die "Build dir '$BUILD_DIR' doesn't exist!"; }

  chdir "$SCRIPT_DIR";  # Current directory isn't always script dir on windows.

  my $config = parseXML("$SCRIPT_DIR/project-config.xml");
  speak(3, Data::Dumper::Dumper($config));

  my ($project, $version, $release, $basename);
  $project  = $config->{project}{ATTR}{title};
  $version  = $config->{project}{version}{DATA};
  $release  = $config->{project}{release}{DATA};
  $basename = "$project-$version";
  $basename .= "-$release" if ($release);

  speak(1, "Project: $basename");


### create directory tree

  $BASEDIR = "$BUILD_DIR/$basename";

  # clear out build directory
  if (-d $BASEDIR) {
    speak(-1, "$BASEDIR already exists, removing... >:-]\n");
    &File::Path::rmtree($BASEDIR);
  }

  process_directory();

## make the ZIP file
  chdir "$BASEDIR/..";
  my $zipFilename = "$basename.zip";
  my $zipFilepath = "$BUILD_DIR/$zipFilename";
  speak(1, "Making ZIP file $zipFilename");

  # create zip archive
  my $zip = Archive::Zip->new();

  # add files
  $zip->addTree($BASEDIR, $basename);

  # save zip to file
  $zip->writeToFileNamed($zipFilepath);
  unless (-f $zipFilepath) { die "zip file '$zipFilepath' didn't get created!"; }
  unless (-s $zipFilepath) { die "zip file '$zipFilepath' is zero bytes!"; }
  print "Created zip file here:\n$zipFilepath\n";

## remove the basedir
  unless ($BASEDIR) { die "\$BASEDIR not defined!"; }
  &File::Path::rmtree($BASEDIR) if $REMOVE_BASEDIR_AFTER_ZIPPING;

}


# ------------------------------------------------------------------------
# Function    :
# Description : handle _one_ file
# Usage       :
# ------------------------------------------------------------------------

sub process_one_file {
  my ($attr, $target) = @_;
  my $cwd = Cwd::getcwd();

  $target =~ s/\/$//;
  $target .= '/';
  my $destination = $target . $attr->{REALNAME};

# copy file first
  my $sourceFile = "$attr->{REALNAME}";
  my $targetFile = "$destination";

  speak(1, "   copying $sourceFile to $targetFile");

  unless (-e $sourceFile) {

    speak(0, "!!!! SKIPPING FILE '$sourceFile', file doesn't exist!\n(Current Directory: $cwd)\n");
    return;
  }
  &File::Copy::copy($sourceFile, $targetFile) or die "Copy failed: $!";


  my $masonize = $attr->{masonize} || '';
  if ($masonize =~ /yes|on|1/i) {
    speak(1, "   > masonizing to $destination...");
    my $args = $attr->{args} || '';
    my @vars = split (/\s*,\s*/, $args);
    my %args = ();
    foreach my $i (@vars) {
      $args{$i} = eval '$' . $i;
      speak(1, "      > argument: $i => $args{$i}");
    }
    my $outbuf;
    my $interp = HTML::Mason::Interp->new(
      comp_root  => $target,
      out_method => \$outbuf);
    $interp->exec("/$attr->{REALNAME}", %args);
    open(FILE, "> $destination");
    print FILE $outbuf;
    close(FILE);
  }

  chdir($cwd); # just in case mason chdir'd on us (like in /examples/index.html)
}

# ------------------------------------------------------------------------
# Function    :
# Description : handle some files
# Usage       :
# ------------------------------------------------------------------------

sub process_files {
    my ($files, $target) = @_;

    # proceed with the explicitely required files first
    my %options = ();
    foreach my $i (@{$files}) {
        $options{$i->{ATTR}{name}} = $i->{ATTR};
    }

    foreach my $i (@{$files}) {
        my @expanded = glob "$i->{ATTR}{name}";
        foreach my $file (@expanded) {
            $i->{ATTR}{REALNAME} = $file;
            if (defined $options{$file}) {
                unless (defined $options{$file}->{PROCESSED}) {
                    speak(1, "EXPLICIT FILE: $file");
                    $options{$file}->{REALNAME} = $file;
                    process_one_file($options{$file}, $target);
                    $options{$file}->{PROCESSED} = 1;
                }
            } else {
                speak(2, "GLOB: $file");
                process_one_file($i->{ATTR}, $target);
                $options{$file} = 2;
            }
        }
    }
}


# ------------------------------------------------------------------------
# Function    :
# Description : handle _one_ directory
# Usage       :
# ------------------------------------------------------------------------

sub process_directory {
    my ($dir, $path) = @_;
    my $cwd = '..';             # ;-)

    # error checking
    unless ($BASEDIR) { die "\$BASEDIR not defined!"; }

#    unless (defined $dir)  { $dir = $SCRIPT_DIR; }
    unless (defined $dir)  { $dir = '.'; }
    unless (defined $path) { $path = ''; }
    speak(2, "DIR: $path$dir");
    $dir =~ s/\/$//;
    $dir .= '/';

    unless (-d $dir) {
        speak(-1, "!!!! DIRECTORY '$dir' NOT FOUND, SKIPPING");
        return 0;
    }

    # go where we have stuff to do
    chdir $dir;

    my $target = $BASEDIR;
    if ($path =~ /\S/) { $target .= "/$path"; }
    if ($dir ne './')  { $target .= $dir; }

    speak(1, "*** Creating directory: $target");
    mkdir $target || die "can't mkdir '$target'";

    unless (-f 'makefile.xml') {
        my $cwd = Cwd::getcwd();
        speak(-1, "No makefile.xml in this directory '$cwd'");
        chdir $cwd;
        return 0;
    }
    my $config = parseXML("makefile.xml");
    speak(3, Data::Dumper::Dumper($config));

    my $tmp = $config->{files}{file};
    if (defined $tmp) {
        my $files;
        if (ref($tmp) eq 'ARRAY') {
            $files = $tmp;
        } else {
            $files = [ $tmp ];
        }
        process_files($files, $target);
    }

    $tmp = $config->{files}{dir};
    if (defined $tmp) {
        my $subdirs;
        if (ref($tmp) eq 'ARRAY') {
            $subdirs = $tmp;
        } else {
            $subdirs = [ $tmp ];
        }
        foreach my $i (@{$subdirs}) {
            process_directory($i->{ATTR}{name}, $path.$dir);
        }
    }

    # get back to our previous location
    chdir $cwd;
}



# ------------------------------------------------------------------------
# Function    :
# Description : do all XML parsing we require
# Usage       :
# ------------------------------------------------------------------------

sub parseXML {
    my $filename = shift || die "no filename specified!";
    my $rethash = {};

    my @tagstack;

    my $handler_start = sub {
        my ($parser, $tag, @attrs) = @_;
        my $current_tag = {};
        $current_tag->{NAME} = $tag;
        $current_tag->{DATA} = '';
        push @tagstack, $current_tag;
        if (scalar @attrs) {
            my $attrs = {};
            $current_tag->{ATTR} = $attrs;
            while (scalar @attrs) {
                my $name = shift @attrs;
                my $value = shift @attrs;
                $attrs->{$name} = $value;
            }
        }
    };

    my $handler_char = sub {
        my ($parser, $data) = @_;
        if ($data =~ /\S/) {
            $tagstack[$#tagstack]->{DATA} .= $data;
        }
    };

    my $handler_end = sub {
        my $current_tag = pop @tagstack;
        if (scalar @tagstack) {
            my $tmp = $tagstack[$#tagstack]->{$current_tag->{NAME}};
            if (defined $tmp) {
                ## better build an array, there are more elements with this tagname
                if (ref($tmp) eq 'ARRAY') {
                    ## oops, the ARRAY is already there, just add the new element
                    push @{$tmp}, $current_tag;
                } else {
                    ## create the array "in-place"
                    $tagstack[$#tagstack]->{$current_tag->{NAME}} = [ $tmp, $current_tag ];
                }
            } else {
                $tagstack[$#tagstack]->{$current_tag->{NAME}} = $current_tag;
            }
        } else {
            $rethash->{$current_tag->{NAME}} = $current_tag;
        }
    };

    my $parser = new XML::Parser
      ( Handlers => { Start => $handler_start,
                      Char  => $handler_char,
                      End   => $handler_end } );
    $parser->parsefile($filename);

    return $rethash;
}


# ------------------------------------------------------------------------
# Function    : speak
# Description : print something according to the level of verbosity
#               receives: verbosity_level and message
#               prints message if verbosity_level >= $SPEAK_VERBOSITY (global)
# Usage       :
# ------------------------------------------------------------------------

sub speak {
    my ($verbosityLevelRequired, $message) = @_;
    if ($verbosityLevelRequired < 0) {
        print STDERR "\033[1;31m!! $message\033[0m\n";
    }
    elsif ($SPEAK_VERBOSITY >= $verbosityLevelRequired) {
        print $message, "\n";
    }
}

# ------------------------------------------------------------------------
