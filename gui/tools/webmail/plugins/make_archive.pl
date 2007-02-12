#!/usr/bin/perl
#
# This all could (maybe) be done in a shell script, but I suck at those.

$i = 0;
$Verbose = 0;
$Plugin = "";
$Version = "";
$SMVersion = "";

foreach $arg (@ARGV)
{
    if ($arg eq "-v")
    {
        $Verbose = 1;
    }
    elsif ($Plugin eq "")
    {
        $Plugin = $arg;
    }
    elsif ($Version eq "")
    {
        $Version = $arg;
    }
    elsif ($SMVersion eq "")
    {
        $SMVersion = $arg;
    }
    else
    {
        print "Unrecognized argument:  $arg\n";
	exit(0);
    }
}

if ($SMVersion eq "")
{
    print "Syntax:  make_archive.pl [-v] plugin_name version sm_version\n";
    print "-v = be verbose\n";
    print "plugin_name:  The name of the plugin\n";
    print "version:  The plugin's version number (1.0, 2.3, etc)\n";
    print "sm_version:  The oldest version of SquirrelMail that this\n";
    print "  plugin is for sure compatible with (1.0.1, 0.5, 1.1.0, etc)\n";
    exit(0);
}


print "Validating name and version\n" if ($Verbose);
$Plugin =~ s/\///g;
if ($Plugin =~ /[^a-z_]/)
{
    print "Plugin name can only contain a-z and _\n";
    exit(0);
}
if ($Version =~ /[^\.0-9]/ || $SMVersion =~ /[^\.0-9]/)
{
    print "Version numbers can only have 0-9 and period\n";
    exit(0);
}

VerifyPluginDir($Plugin);

print "Getting file list.\n" if ($Verbose);
@Files = RecurseDir($Plugin);

$QuietString = " > /dev/null 2> /dev/null" if (! $Verbose);

print "\n\n" if ($Verbose);
print "Creating $Plugin.$Version-$SMVersion.tar.gz\n";
system("tar cvfz $Plugin.$Version-$SMVersion.tar.gz $Plugin" . 
    FindTarExcludes(@Files) . $QuietString);
    
#print "\n\n" if ($Verbose);
#print "Creating $Plugin.$Version-$SMVersion.zip\n";
#system("zip -r $Plugin.$Version-$SMVersion.zip $Plugin/" . 
#    FindZipExcludes(@Files) . $QuietString);



sub VerifyPluginDir
{
    local ($Plugin) = @_;
    
    if (! -e $Plugin && ! -d $Plugin)
    {
        print "The $Plugin directory doesn't exist, " .
	    "or else it is not a directory.\n";
        exit(0);
    }
}


sub FindTarExcludes
{
    local (@Files) = @_;
    
    $ExcludeStr = "";
    
    foreach $File (@Files)
    {
        if ($File =~ /^(.*\/CVS)\/$/)
	{
	    $ExcludeStr .= " --exclude $1";
	}
    }
    
    return $ExcludeStr;
}

sub FindZipExcludes
{
    local (@Files) = @_;
    
    $ExcludeStr = "";
    
    foreach $File (@Files)
    {
        if ($File =~ /^(.*\/CVS)\/$/)
	{
	    $ExcludeStr .= " $1/ $1/*";
	}
    }
    
    if ($ExcludeStr ne "")
    {
        $ExcludeStr = " -x" . $ExcludeStr;
    }
    
    return $ExcludeStr;
}

sub RecurseDir
{
    local ($Dir) = @_;
    local (@Files, @Results);
    
    opendir(DIR, $Dir);
    @Files = readdir(DIR);
    closedir(DIR);
    
    @Results = ("$Dir/");
    
    foreach $file (@Files)
    {
        next if ($file =~ /^[\.]+/);
        if (-d "$Dir/$file")
	{
	    push (@Results, RecurseDir("$Dir/$file"));
	}
	else
	{
	    push (@Results, "$Dir/$file");
	}
    }
    
    return @Results;
}
