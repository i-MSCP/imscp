=head1 NAME

 iMSCP::Provider::Service::Upstart - Base service provider for `upstart` jobs

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package iMSCP::Provider::Service::Upstart;

use strict;
use warnings;
use File::Basename;
use File::Spec;
use iMSCP::Execute;
use iMSCP::File;
use version;
use parent 'iMSCP::Provider::Service::Sysvinit';

delete $ENV{'UPSTART_SESSION'}; # See IP-1514

# Private variables
my $upstartVersion;
my $START_ON = qr/^\s*start\s+on/;
my $COMMENTED_START_ON = qr/^\s*#+\s*start\s+on/;
my $MANUAL = qr/^\s*manual\s*$/;

# Commands used in that package
my %commands = (
    start   => '/sbin/start',
    stop    => '/sbin/stop',
    restart => '/sbin/restart',
    reload  => '/sbin/reload',
    status  => '/sbin/status',
    initctl => '/sbin/initctl'
);

# Paths where job files must be searched
my @jobFilePaths = ( '/etc/init' );

# Cache for job file paths
my %jobFilesCache = ();

=head1 DESCRIPTION

 Base service provider for `upstart` jobs.

 See: http://upstart.ubuntu.com

=head1 PUBLIC METHODS

=over 4

=item isEnabled($job)

 Is the given job is enabled?

 Return bool TRUE if the given job is enabled, FALSE otherwise

=cut

sub isEnabled
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );
    my $jobFileContent = $self->_readJobFile( $job );
    return $self->_isEnabledPre067( $jobFileContent ) if $self->_versionIsPre067();
    return $self->_isEnabledPre090( $jobFileContent ) if $self->_versionIsPre090();
    return $self->_isEnabledPost090(
        $jobFileContent, $self->_readJobOverrideFile( $job )
    ) if $self->_versionIsPost090();
    0;
}

=item enable($job)

 Enable the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );
    my $jobFileContent = $self->_readJobFile( $job );
    return $self->_enablePre090( $job, $jobFileContent ) if $self->_versionIsPre090();
    $self->_enablePost090( $job, $jobFileContent, $self->_readJobOverrideFile( $job ) );
}

=item disable($job)

 Disable the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );
    return $self->_disablePre067( $job, $self->_readJobFile( $job ) ) if $self->_versionIsPre067();
    return $self->_disablePre090( $job, $self->_readJobFile( $job ) ) if $self->_versionIsPre090();
    return $self->_disablePost090( $job, $self->_readJobOverrideFile( $job ) ) if $self->_versionIsPost090();
    0;
}

=item remove($job)

 Remove the given job

 Param string $job job name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );
    return 0 unless $self->stop( $job );

    local $@;
    for my $jobFileType('conf', 'override') {
        if (my $filepath = eval { $self->getJobFilePath( $job, $jobFileType ); }) {
            delete $jobFilesCache{$job.'.'.$jobFileType};
            return 0 if iMSCP::File->new( filename => $filepath )->delFile();
        }
    }

    1;
}

=item start($service)

 Start the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub start
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        return $self->_exec( $commands{'start'}, $job ) == 0 unless $self->isRunning( $job );
        return 1;
    }

    $self->SUPER::start( $job );
}

=item stop($job)

 Stop the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub stop
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        return $self->_exec( $commands{'stop'}, $job ) == 0 if $self->isRunning( $job );
        return 1;
    }

    $self->SUPER::stop( $job );
}

=item restart($job)

 Restart the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub restart
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        return $self->_exec( $commands{'restart'}, $job ) == 0 if $self->isRunning( $job );
        return $self->start( $job );
    }

    $self->SUPER::restart( $job );
}

=item reload($service)

 Reload the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub reload
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        return $self->_exec( $commands{'reload'}, $job ) == 0 if $self->isRunning( $job );
        return $self->start( $job );
    }

    $self->SUPER::reload( $job );

}

=item isRunning($service)

 Is the given job is running?

 Param string $job Job name
 Return bool TRUE if the given job is running, FALSE on failure

=cut

sub isRunning
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        execute( "$commands{'status'} $job", \ my $stdout, \ my $stderr );
        return $stdout =~ m%start/%;
    }

    $self->SUPER::isRunning( $job );
}

=item getJobFilePath($job [ , $jobFileType = 'conf' ])

 Get full path of the job configuration file or job override file which belongs to the given job

 Param string $job Job name
 Param string $jobFileType OPTIONAL Job file type ('conf'|'override') - Default to 'conf'
 Return string job file path on success, die on failure

=cut

sub getJobFilePath
{
    my ($self, $job, $jobFileType) = @_;

    defined $job or die( 'parameter $job is not defined' );
    $jobFileType ||= 'conf';
    $self->_searchJobFile( $job, $jobFileType );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _getVersion()

 Get upstart version

 Return string Upstart version

=cut

sub _getVersion
{
    ($upstartVersion) = `initctl --version` =~ /initctl \(upstart\s+([^\)]*)\)/ unless $upstartVersion;
    $upstartVersion;
}

=item _isUpstart($service)

 Is the given job an upstart job?

 Param string $job Job name
 Return bool TRUE if the given job is managed by an upstart job, FALSE otherwise

=cut

sub _isUpstart
{
    my ($self, $job) = @_;

    local $@;
    eval { $self->_searchJobFile( $job ); };
}

=item _versionIsPre067()

 Is upstart version pre 0.6.7?

 Return bool TRUE if upstart version is pre 0.6.7, FALSE otherwise

=cut

sub _versionIsPre067
{
    my $self = shift;

    version->parse( $self->_getVersion() ) < version->parse( '0.6.7' );
}

=item _versionIsPre090()

 Is upstart version pre 0.9.0?

 Return bool TRUE if upstart version is pre 0.9.0, FALSE otherwise

=cut

sub _versionIsPre090
{
    my $self = shift;

    version->parse( $self->_getVersion() ) < version->parse( '0.9.0' );
}

=item _versionIsPost090()

 Is upstart version post 0.9.0?

 Return bool TRUE if upstart version is post 0.9.0, FALSE otherwise

=cut

sub _versionIsPost090
{
    my $self = shift;

    version->parse( $self->_getVersion() ) >= version->parse( '0.9.0' );
}

=item _isEnabledPre067($jobFileContent)

 Is the given job enabled for upstart versions < 0.6.7?

 Param string $jobFileContent job file content
 Return bool TRUE if the given job is enabled, FALSE otherwise

=cut

sub _isEnabledPre067
{
    my ($self, $jobFileContent) = @_;

    # Upstart version < 0.6.7 means no manual stanza.
    $jobFileContent =~ /$START_ON/;
}

=item _isEnabledPre090($jobFileContent)

 Is the given job enabled for upstart versions < 0.9.0?

 Param string $jobFileContent job file content
 Return bool TRUE if the given job is enabled, FALSE otherwise

=cut

sub _isEnabledPre090
{
    my ($self, $jobFileContent) = @_;

    # Upstart versions < 0.9.0 means no override files. Thus, we check to see if an uncommented `start on` or `manual`
    # stanza is the last one in the file. The last one in the file wins.
    my $enabled = 0;
    for(split /^/, $jobFileContent) {
        if (/$START_ON/) {
            $enabled = 1;
        } elsif (/$MANUAL/) {
            $enabled = 0;
        }
    }

    $enabled;
}

=item _isEnabledPost090($jobFileContent, $jobOverrideFileContent)

 Is the given job enabled for upstart versions >= 0.9.0?

 Param string $jobFileContent job file content
 Param string $jobOverrideFileContent job override file content
 Return bool TRUE if the given job is enabled, FALSE otherwise

=cut

sub _isEnabledPost090
{
    my ($self, $jobFileContent, $jobOverrideFileContent) = @_;

    # Upstart versions >= 0.9.0 has `manual` stanzas and override files. Thus, we check to see if an uncommented
    # `start on` or `manual` stanza is the last one in the conf file and any override files. The last one in the file
    # wins.
    my $enabled = 0;
    for($jobFileContent, $jobOverrideFileContent) {
        next unless defined;
        for(split /^/) {
            if (/$START_ON/) {
                $enabled = 1;
            } elsif (/$MANUAL/) {
                $enabled = 0;
            }
        }
    }

    $enabled;
}

=item _enablePre090($job, $jobFileContent)

 Enable the given job for upstart versions < 0.9.0

 Param string $job Job name
 Param string $jobFileContent job file content
 Return bool TRUE on success, die on failure

=cut

sub _enablePre090
{
    my ($self, $job, $jobFileContent) = @_;

    # We also need to remove any manual stanzas to ensure that it is enabled
    $jobFileContent = $self->_removeManualStanza( $jobFileContent );

    unless ($self->_isEnabledPre090( $jobFileContent )) {
        $jobFileContent = $jobFileContent =~ /$COMMENTED_START_ON/
            ? $self->_uncommentStartOnStanza( $jobFileContent )
            : $self->_addDefaultStartOnStanza( $jobFileContent );
    }

    return $self->_writeFile( $job, $jobFileContent );
}

=item _enablePost090($job, $jobFileContent, $jobOverrideFileContent)

 Enable the given job for upstart versions >= 0.9.0

 Param string $job Job name
 Param string $jobFileContent job file content
 Param string $jobOverrideFileContent job override file content
 Return bool TRUE on success, die on failure

=cut

sub _enablePost090
{
    my ($self, $job, $jobFileContent, $jobOverrideFileContent) = @_;

    $jobOverrideFileContent = $self->_removeManualStanza( $jobOverrideFileContent );
    unless ($self->_isEnabledPost090( $jobFileContent, $jobOverrideFileContent )) {
        $jobOverrideFileContent .= $jobFileContent =~ /$START_ON/
            ? $self->_extractStartOnStanza( $jobFileContent )
            : "\nstart on runlevel [2345]";

        return $self->_writeFile( "$job.override", $jobOverrideFileContent );
    }

    1;
}

=item _disablePre067($service, $jobFileContent)

 Disable the given job for upstart versions < 0.6.7

 Param string $job Job name
 Param string $jobFileContent job file content
 Return bool TRUE on success, die on failure

=cut

sub _disablePre067
{
    my ($self, $job, $jobFileContent) = @_;

    $jobFileContent = $self->_commentStartOnStanza( $jobFileContent );
    $self->_writeFile( "$job.conf", $jobFileContent );
}

=item _disablePre090($service, $jobFileContent)

 Disable the given job for upstart versions < 0.9.0

 Param string $job Job name
 Param string $jobFileContent job file content
 Return bool TRUE on success, die on failure

=cut

sub _disablePre090
{
    my ($self, $job, $jobFileContent) = @_;

    $self->_writeFile( "$job.conf", $self->_ensureDisabledWithManualStanza( $jobFileContent ) );
}

=item _disablePost090($service, $jobOverrideFileContent)

 Disable the given job for upstart versions >= 0.9.0

 Param string $job Job name
 Param string $jobOverrideFileContent job $jobOverrideFileContent file content
 Return bool TRUE on success, die on failure

=cut

sub _disablePost090
{
    my ($self, $job, $jobOverrideFileContent) = @_;

    $self->_writeFile( "$job.override", $self->_ensureDisabledWithManualStanza( $jobOverrideFileContent ) );
}

=item _uncomment($line)

 Uncomment the given line

 Param string $line
 Return string Uncommented line

=cut

sub _uncomment
{
    $_[1] =~ s/^(\s*)#+/$1/r;
}

=item _removeTrailingCommentsFromCommentedLine($line)

 Remove any trailing comments from the given commented line

 Param string $line Line to process
 Return string

=cut

sub _removeTrailingCommentsFromCommentedLine
{
    $_[1] =~ s/^(\s*#+\s*[^#]*).*/$1/r;
}

=item _removeTrailingComments($line)

 Remove any trailing comments from the given line

 Param string $line Line to process
 Return string String without any trailing comments

=cut

sub _removeTrailingComments
{
    $_[1] =~ s/^(\s*[^#]*).*/$1/r;
}

=item _countUnbalancedParentheses($line)

 Return number of unbalanced parentheses in the given line

 Param string $line Line to process
 Return int

=cut

sub _countUnbalancedParentheses
{
    my $line = $_[1];

    ( $line =~ tr/(/(/ ) - ( $line =~ tr/)/)/ );
}

=item _removeManualStanza($string)

 Remove any upstart `manual` stanza in the given $string

 Param string $string String to process
 Return string String without upstart `manual` stanza

=cut

sub _removeManualStanza
{
    $_[1] =~ s/$MANUAL//r;
}

=item _commentStartOnStanza($text)

 Comment any upstart `start on` stanza in the given text

 Param string $text Text to process
 Return string Text with commented upstart `start on` stanza if any

=cut

sub _commentStartOnStanza
{
    my ($self, $text) = @_;

    my $parentheses = 0;

    join '',
        map {
            if (/$START_ON/ || $parentheses > 0) {
                # If there are more opening parens than closing parens, we need to comment out a multiline 'start on'
                # stanza
                $parentheses += $self->_countUnbalancedParentheses( $self->_removeTrailingComments( $_ ) );
                '#'.$_;
            } else {
                $_;
            }
        } split /^/, $text;
}

=item _uncommentStartOnStanza($text)

 Uncomment any upstart `start on` stanza in the given text

 Param string Text to process
 Return string Text with uncommented upstart `start on `stanza` if any

=cut

sub _uncommentStartOnStanza
{
    my ($self, $text) = @_;

    my $parentheses = 0;

    join '',
        map {
            if (/$COMMENTED_START_ON/ || $parentheses > 0) {
                # If there are more opening parentheses than closing parentheses, we need to comment out a multiline
                # 'start on' stanza
                $parentheses += $self->_countUnbalancedParentheses( $self->_removeTrailingCommentsFromCommentedLine( $_ ) );
                $self->_uncomment( $_ );
            } else {
                $_;
            }
        } split /^/, $text;
}

=item _extractStartOnStanza($text)

 Extract the upstart `start on` stanza from the given text if any

 Param string $text Text to process
 Return string Text without any upstart `start in stanza`

=cut

sub _extractStartOnStanza
{
    my ($self, $text) = @_;

    my $parentheses = 0;

    join '',
        map {
            if (/$START_ON/ || $parentheses > 0) {
                $parentheses += $self->_countUnbalancedParentheses( $self->_removeTrailingComments( $_ ) );
                $_;
            }
        } split /^/, $text;
}

=item _addDefaultStartOnStanza($text)

 Add default upstart `start on` stanza in the given text

 Param string $text Text to process
 Return string Text with upstart default `start on` stanza

=cut

sub _addDefaultStartOnStanza
{
    $_[1]."\nstart on runlevel [2345]";
}

=item _ensureDisabledWithManualStanza($text)

 Ensure that the given text contains the upstart `manual` stanza

 Param string $text Text to process
 Return string Text with upstart `manual` stanza

=cut

sub _ensureDisabledWithManualStanza
{
    my ($self, $text) = @_;

    $self->_removeManualStanza( $text )."\nmanual";
}

=item _searchJobFile($job, $jobFileType)

 Search the job configuration file or job override file which belongs to the given job in all available paths

 Param string $job Job name
 Param string $jobFileType Job file type ('conf'|'override')
 Return string Job file path on success, die on failure

=cut

sub _searchJobFile
{
    my ($self, $job, $jobFileType) = @_;

    my $jobFile = $job.'.'.($jobFileType || 'conf');

    return $jobFilesCache{$jobFile} if $jobFilesCache{$jobFile};

    for my $path(@jobFilePaths) {
        my $filepath = File::Spec->join( $path, $jobFile );
        return $jobFilesCache{$jobFile} = $filepath if -f $filepath;
    }

    die( sprintf( 'Could not find the upstart %s job file', $jobFile ) );
}

=item _readJobFile($job)

 Read the job file which belongs to the given job

 Param string $job Job name
 Return string Job file content on success, die on failure

=cut

sub _readJobFile
{
    my ($self, $job) = @_;

    my $filepath = $self->getJobFilePath( $job );
    iMSCP::File->new( filename => $filepath )->get() or die( sprintf( 'Could not read %s file', $filepath ) );
}

=item _readJobOverrideFile($job)

 Read the job override file which belongs to the given job

 Param string job Job name
 Return string Job override file content on success, die on failure

=cut

sub _readJobOverrideFile
{
    my ($self, $job) = @_;

    if ((my $filepath = eval { $self->getJobFilePath( $job, 'override' ); })) {
        return iMSCP::File->new( filename => $filepath )->get() or die(
            sprintf( 'Could not read %s file', $filepath )
        );
    }

    '';
}

=item _writeFile($filename, $fileContent)

 Write the given job file (job configuration file or job override file)

 Param string $filename file name
 Param string $fileContent file content
 Return bool TRUE on success, die on failure

=cut

sub _writeFile
{
    my ($self, $filename, $fileContent) = @_;

    my $jobDir = dirname( $self->getJobFilePath( basename( $filename, '.conf', '.override' ) ) );
    my $filepath = File::Spec->join( $jobDir, $filename );
    my $file = iMSCP::File->new( filename => $filepath );
    $file->set( $fileContent ) == 0 && $file->save() == 0 && $file->mode( 0644 ) == 0 or die(
        sprintf( 'Could not write %s file', $filepath )
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
