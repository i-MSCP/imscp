=head1 NAME

 iMSCP::Provider::Service::Upstart - Upstart init provider

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
use Carp 'croak';
use File::Basename qw/ basename dirname /;
use File::Spec;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::File;
use version;
use parent 'iMSCP::Provider::Service::Abstract';

# Commands used in that package
our %COMMANDS = (
    start   => '/sbin/start',
    stop    => '/sbin/stop',
    restart => '/sbin/restart',
    reload  => '/sbin/reload',
    status  => '/sbin/status',
    initctl => '/sbin/initctl'
);

# Private variables
my $START_ON = qr/^\s*start\s+on/;
my $COMMENTED_START_ON = qr/^\s*#+\s*start\s+on/;
my $MANUAL = qr/^\s*manual\s*/m;

# Paths where job files must be searched
my @JOBFILEPATHS = ( '/etc/init' );

# Operate against system Upstart, not session (See IP-1514)
delete $ENV{'UPSTART_SESSION'};

=head1 DESCRIPTION

 Upstart init provider.

 See: http://upstart.ubuntu.com

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $job )

 See iMSCP::Provider::Service::Interface::isEnabled()

=cut

sub isEnabled
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->_versionIsPre067() ) {
        $self->_isEnabledPre067( $self->_readJobFile( $job ));
        return;
    }

    if ( $self->_versionIsPre090() ) {
        $self->_isEnabledPre090( $self->_readJobFile( $job ));
        return;
    }

    $self->_isEnabledPost090( $self->_readJobFile( $job ), $self->_readJobOverrideFile( $job ));
}

=item enable( $job )

 See iMSCP::Provider::Service::Interface::enable()

=cut

sub enable
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->_versionIsPre090() ) {
        $self->_enablePre090( $job, $self->_readJobFile( $job ));
        return;
    }

    $self->_enablePost090( $job, $self->_readJobFile( $job ), $self->_readJobOverrideFile( $job ));
}

=item disable( $job )

 See iMSCP::Provider::Service::Interface::disable()

=cut

sub disable
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->_versionIsPre067() ) {
        $self->_disablePre067( $job, $self->_readJobFile( $job ));
        return;
    }

    if ( $self->_versionIsPre090() ) {
        $self->_disablePre090( $job, $self->_readJobFile( $job ));
        return;
    }

    $self->_disablePost090( $job, $self->_readJobOverrideFile( $job ));
}

=item remove( $job )

 See iMSCP::Provider::Service::Interface::remove()

=cut

sub remove
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    $self->stop( $job ) if $self->hasService( $job );

    # Even if there is no job file, there can be still orphaned job override
    # file which we need to remove. Thus, we always process both files.
    for my $type ( qw/ conf override / ) {
        next unless my $jobFilePath = eval { $self->resolveJob( $job, $type, TRUE ); };
        debug( sprintf( "Removing the %s Upstart job file", $jobFilePath ));
        iMSCP::File->new( filename => $jobFilePath )->delFile() == 0 or croak(
            getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
        );
    }
}

=item start( $job )

 See iMSCP::Provider::Service::Interface::start()

=cut

sub start
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    $self->_exec( [ $COMMANDS{'start'}, $job ] ) unless $self->isRunning( $job );
}

=item stop( $job )

 See iMSCP::Provider::Service::Interface::stop()

=cut

sub stop
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    $self->_exec( [ $COMMANDS{'stop'}, $job ] ) if $self->isRunning( $job );
}

=item restart( $job )

 See iMSCP::Provider::Service::Interface::restart()

=cut

sub restart
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    $self->isRunning( $job ) ? $self->_exec( [ $COMMANDS{'restart'}, $job ] ) : $self->_exec( [ $COMMANDS{'start'}, $job ] );
}

=item reload( $job )

 See iMSCP::Provider::Service::Interface::reload()

=cut

sub reload
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->isRunning( $job ) ) {
        # We need to catch STDERR as we do do want croak on failure
        my $ret = $self->_exec( [ $COMMANDS{'reload'}, $job ], undef, \my $stderr );
        # If the reload action failed, we try a restart instead. This cover
        # case where the reload action is not supported.
        $self->restart( $job ) unless $ret;
        return;
    }

    $self->start( $job );
}

=item isRunning( $job )

 See iMSCP::Provider::Service::Interface::isRunning()

=cut

sub isRunning
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    $self->_exec( [ $COMMANDS{'status'}, $job ], \my $stdout );
    return $stdout =~ /start/;
}

=item hasService( $job )

 See iMSCP::Provider::Service::Interface::hasService()

=cut

sub hasService
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    eval { $self->resolveJob( $job, undef, TRUE ); };
}

=item resolveJob( $job [, $type = 'conf' [, $nocache ] ] )

 Resolve the given Upstart job

 Param string $job Job name
 Param string $type OPTIONAL Job file type (conf|override)
 Param boolean $nocache OPTIONAL If true, no cache will be used
 Return string Full Upstart job file path on success, croak on failure

=cut

sub resolveJob
{
    my ( $self, $job, $type, $nocache ) = @_;
    $type //= 'conf';

    defined $job or croak( 'Missing or undefined $job parameter' );

    CORE::state %resolved;

    my $jobFile = $job . '.' . $type;

    if ( $nocache ) {
        delete $resolved{$jobFile};
    } elsif ( exists $resolved{$jobFile} ) {
        $resolved{$jobFile} or croak( sprintf( "Couldn't resolve the %s Upstart job file", $jobFile ));
        return $resolved{$jobFile};
    }

    for my $path ( @JOBFILEPATHS ) {
        my $filepath = File::Spec->join( $path, $jobFile );
        $resolved{$jobFile} = $filepath if -f $filepath;
    }

    if ( $nocache ) {
        $resolved{$jobFile} or croak( sprintf( "Couldn't resolve the %s Upstart job file", $jobFile ));
        return delete $resolved{$jobFile};
    }

    $resolved{$jobFile} or croak( sprintf( "Couldn't resolve the %s Upstart job file", $jobFile ));
}

=back

=head1 PRIVATE METHODS

=over 4

=item _getVersion( )

 Get upstart version

 Return string Upstart version

=cut

sub _getVersion
{
    CORE::state $version;

    ( $version ) = `initctl --version` =~ /initctl \(upstart\s+([^\)]*)\)/ unless $version;
    $version;
}

=item _versionIsPre067( )

 Is Upstart version pre 0.6.7?

 Return boolean TRUE if Upstart version is pre 0.6.7, FALSE otherwise

=cut

sub _versionIsPre067
{
    my ( $self ) = @_;

    version->parse( $self->_getVersion()) < version->parse( '0.6.7' );
}

=item _versionIsPre090( )

 Is Upstart version pre 0.9.0?

 Return boolean TRUE if Upstart version is pre 0.9.0, FALSE otherwise

=cut

sub _versionIsPre090
{
    my ( $self ) = @_;

    version->parse( $self->_getVersion()) < version->parse( '0.9.0' );
}

=item _versionIsPost090( )

 Is Upstart version post 0.9.0?

 Return boolean TRUE if Upstart version is post 0.9.0, FALSE otherwise

=cut

sub _versionIsPost090
{
    my ( $self ) = @_;

    version->parse( $self->_getVersion()) >= version->parse( '0.9.0' );
}

=item _isEnabledPre067( $jobFileContent )

 Is the given job enabled for Upstart versions < 0.6.7?

 Param string $jobFileContent job file content
 Return boolean TRUE if the given job is enabled, FALSE otherwise

=cut

sub _isEnabledPre067
{
    my ( $self, $jobFileContent ) = @_;

    defined $jobFileContent or croak( 'Missing or undefined $jobFileContent parameter' );

    # Upstart version < 0.6.7 means no 'manual' stanza.
    $jobFileContent =~ /$START_ON/;
}

=item _isEnabledPre090( $jobFileContent )

 Is the given job enabled for Upstart versions < 0.9.0?

 Param string $jobFileContent job file content
 Return boolean TRUE if the given job is enabled, FALSE otherwise

=cut

sub _isEnabledPre090
{
    my ( $self, $jobFileContent ) = @_;

    defined $jobFileContent or croak( 'Missing or undefined $jobFileContent parameter' );

    # Upstart versions < 0.9.0 means no override files. Thus,
    # we check to see if an uncommented 'start on' or 'manual'
    # stanza is the last one in the job file. The last one wins.
    open my $fh, '<', \$jobFileContent or croak( sprintf( "Couldn't open in-memory file handle: %s", $! ));
    my $enabled = FALSE;
    while ( my $line = <$fh> ) {
        if ( $line =~ /$START_ON/ ) {
            $enabled = TRUE;
        } elsif ( $line =~ /$MANUAL/ ) {
            $enabled = FALSE;
        }
    }

    $enabled;
}

=item _isEnabledPost090( $jobFileContent, $jobOverrideFileContent )

 Is the given job enabled for Upstart versions >= 0.9.0?

 Param string $jobFileContent job file content
 Param string $jobOverrideFileContent job override file content
 Return boolean TRUE if the given job is enabled, FALSE otherwise

=cut

sub _isEnabledPost090
{
    my ( $self, $jobFileContent, $jobOverrideFileContent ) = @_;

    defined $jobFileContent or croak( 'Missing or undefined $jobFileContent parameter' );
    defined $jobOverrideFileContent or croak( 'Missing or undefined $jobOverrideFileContent parameter' );

    # Upstart versions >= 0.9.0 has 'manual' stanzas and job override
    # files. Thus, we check to see if an uncommented 'start on' or
    # 'manual' stanza is the last one in the conf file and any
    # override files. The last one wins.
    my $enabled = FALSE;
    for my $fileC ( \$jobFileContent, \$jobOverrideFileContent ) {
        open my $fh, '<', $fileC or croak( sprintf( "Couldn't open in-memory file handle: %s", $! ));
        while ( my $line = <$fh> ) {
            if ( $line =~ /$START_ON/ ) {
                $enabled = TRUE;
            } elsif ( $line =~ /$MANUAL/ ) {
                $enabled = FALSE;
            }
        }
    }

    $enabled;
}

=item _enablePre090( $job, $jobFileContent )

 Enable the given job for Upstart versions < 0.9.0

 Param string $job Job name
 Param string $jobFileContent job file content
 Return boolean TRUE on success, croak on failure

=cut

sub _enablePre090
{
    my ( $self, $job, $jobFileContent ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );
    defined $jobFileContent or croak( 'Missing or undefined $jobFileContent parameter' );

    # Remove 'manual' stanzas if any
    $jobFileContent = $self->_removeManualStanzaFrom( $jobFileContent );

    # Add or uncomment 'START ON' stanza if needed
    unless ( $self->_isEnabledPre090( $jobFileContent ) ) {
        $jobFileContent = ( $jobFileContent =~ /$COMMENTED_START_ON/ )
            ? $self->_uncommentStartOnStanzaIn( $jobFileContent )
            : $self->_addDefaultStartOnStanzaTo( $jobFileContent );
    }

    return $self->_writeFile( $job, $jobFileContent );
}

=item _enablePost090( $job, $jobFileContent, $jobOverrideFileContent )

 Enable the given job for Upstart versions >= 0.9.0

 Param string $job Job name
 Param string $jobFileContent job file content
 Param string $jobOverrideFileContent job override file content
 Return boolean TRUE on success, croak on failure

=cut

sub _enablePost090
{
    my ( $self, $job, $jobFileContent, $jobOverrideFileContent ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );
    defined $jobFileContent or croak( 'Missing or undefined $jobFileContent parameter' );
    defined $jobOverrideFileContent or croak( 'Missing or undefined $jobOverrideFileContent parameter' );

    # Remove 'manual' stanzas if any
    $jobOverrideFileContent = $self->_removeManualStanzaFrom( $jobOverrideFileContent );

    # Add or uncomment 'START ON' stanza if needed
    unless ( $self->_isEnabledPost090( $jobFileContent, $jobOverrideFileContent ) ) {
        if ( $jobFileContent =~ /$START_ON/ ) {
            $jobOverrideFileContent .= $self->_extractStartOnStanzaFrom( $jobFileContent );
        } else {
            $jobOverrideFileContent = $self->_addDefaultStartOnStanzaTo( $jobOverrideFileContent );
        }
    }

    $self->_writeFile( $job . '.override', $jobOverrideFileContent );
}

=item _disablePre067( $service, $jobFileContent )

 Disable the given job for Upstart versions < 0.6.7

 Param string $job Job name
 Param string $jobFileContent job file content
 Return boolean TRUE on success, croak on failure

=cut

sub _disablePre067
{
    my ( $self, $job, $jobFileContent ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );
    defined $jobFileContent or croak( 'Missing or undefined $jobFileContent parameter' );

    $jobFileContent = $self->_commentStartOnStanza( $jobFileContent );
    $self->_writeFile( $job . '.conf', $jobFileContent );
}

=item _disablePre090( $service, $jobFileContent )

 Disable the given job for Upstart versions < 0.9.0

 Param string $job Job name
 Param string $jobFileContent job file content
 Return boolean TRUE on success, croak on failure

=cut

sub _disablePre090
{
    my ( $self, $job, $jobFileContent ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );
    defined $jobFileContent or croak( 'Missing or undefined $jobFileContent parameter' );

    $self->_writeFile( $job . '.conf', $self->_ensureDisabledWithManualStanza( $jobFileContent ));
}

=item _disablePost090( $service, $jobOverrideFileContent )

 Disable the given job for Upstart versions >= 0.9.0

 Param string $job Job name
 Param string $jobOverrideFileContent job $jobOverrideFileContent file content
 Return boolean TRUE on success, croak on failure

=cut

sub _disablePost090
{
    my ( $self, $job, $jobOverrideFileContent ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );
    defined $jobOverrideFileContent or croak( 'Missing or undefined $jobOverrideFileContent parameter' );

    $self->_writeFile( $job . '.override', $self->_ensureDisabledWithManualStanza( $jobOverrideFileContent ));
}

=item _uncomment( $line )

 Uncomment the given line

 Param string $line
 Return string Uncommented line

=cut

sub _uncomment
{
    my ( $self, $line ) = @_;

    defined $line or croak( 'Missing or undefined $line parameter' );

    $line =~ s/^(\s*)#+/$1/r;
}

=item _removeTrailingCommentsFromCommentedLine( $line )

 Remove any trailing comments from the given commented line

 Param string $line Line to process
 Return string

=cut

sub _removeTrailingCommentsFromCommentedLine
{
    my ( $self, $line ) = @_;

    defined $line or croak( 'Missing or undefined $line parameter' );

    $line =~ s/^(\s*#+\s*[^#]*).*/$1/r;
}

=item _removeTrailingComments( $line )

 Remove any trailing comments from the given line

 Param string $line Line to process
 Return string String without any trailing comments

=cut

sub _removeTrailingComments
{
    my ( $self, $line ) = @_;

    defined $line or croak( 'Missing or undefined $line parameter' );

    $line =~ s/^(\s*[^#]*).*/$1/r;
}

=item _countUnbalancedRoundBrackets( $line )

 Count number of unbalanced round brackets in the given line

 Param string $line Line to process
 Return int Number of unbalanced round brackets

=cut

sub _countUnbalancedRoundBrackets
{
    my ( $self, $line ) = @_;

    defined $line or croak( 'Missing or undefined $line parameter' );

    ( $line =~ tr/(// )-( $line =~ tr/)// );
}

=item _removeManualStanzaFrom( $string )

 Remove any Upstart 'manual' stanza from the given $string

 Param string $string String to process
 Return string String without Upstart 'manual' stanza

=cut

sub _removeManualStanzaFrom
{
    my ( $self, $line ) = @_;

    defined $line or croak( 'Missing or undefined $line parameter' );

    $line =~ s/$MANUAL//gr;
}

=item _commentStartOnStanza( $text )

 Comment any Upstart 'start on' stanza in the given text

 Param string $text Text to process
 Return string Text with commented Upstart 'start on' stanza if any

=cut

sub _commentStartOnStanza
{
    my ( $self, $text ) = @_;

    defined $text or croak( 'Missing or undefined $text parameter' );

    my $roundBrackets = 0;

    join '', map {
        if ( $roundBrackets > 0 || /$START_ON/ ) {
            # If there are more opening round brackets than closing
            # round brackets, we need to comment out a multiline
            # 'start on' stanza
            $roundBrackets += $self->_countUnbalancedRoundBrackets( $self->_removeTrailingComments( $_ ));
            '#' . $_;
        } else {
            $_;
        }
    } split /^/, $text;
}

=item _uncommentStartOnStanzaIn( $text )

 Uncomment any Upstart 'start on' stanza in the given text

 Param string Text to process
 Return string Text with uncommented Upstart 'start on' stanza if any

=cut

sub _uncommentStartOnStanzaIn
{
    my ( $self, $text ) = @_;

    defined $text or croak( 'Missing or undefined $text parameter' );

    my $roundBrackets = 0;
    join '', map {
        if ( $roundBrackets > 0 || /$COMMENTED_START_ON/ ) {
            # If there are more opening round brackets than closing
            # round brackets, we need to comment out a multiline
            # 'start on' stanza
            $roundBrackets += $self->_countUnbalancedRoundBrackets(
                $self->_removeTrailingCommentsFromCommentedLine( $_ )
            );
            $self->_uncomment( $_ );
        } else {
            $_;
        }
    } split /^/, $text;
}

=item _extractStartOnStanzaFrom( $string )

 Extract the Upstart 'start on' stanza from the given string if any

 Param string $string String to process
 Return string Text without any Upstart 'start in stanza'

=cut

sub _extractStartOnStanzaFrom
{
    my ( $self, $string ) = @_;

    defined $string or croak( 'Missing or undefined $string parameter' );

    my $roundBrackets = 0;
    join '', map {
        if ( $roundBrackets > 0 || /$START_ON/ ) {
            $roundBrackets += $self->_countUnbalancedRoundBrackets( $self->_removeTrailingComments( $_ ));
            $_;
        }
    } split /^/, $string;
}

=item _addDefaultStartOnStanzaTo( $string )

 Add default Upstart 'start on' stanza to the given string

 Param string $string String into which default 'start on' stanza must be added
 Return string Text with Upstart default 'start on' stanza

=cut

sub _addDefaultStartOnStanzaTo
{
    my ( $self, $string ) = @_;

    defined $string or croak( 'Missing or undefined $string parameter' );

    $string . "\nstart on runlevel [2345]\n";
}

=item _ensureDisabledWithManualStanza( $string )

 Ensure that the given string contains the Upstart 'manual' stanza

 Param string $string String to process
 Return string String with Upstart 'manual' stanza

=cut

sub _ensureDisabledWithManualStanza
{
    my ( $self, $string ) = @_;

    defined $string or croak( 'Missing or undefined $string parameter' );

    $self->_removeManualStanzaFrom( $string ) . "manual\n";
}

=item _readJobFile( $job )

 Read the job file which belongs to the given job

 Param string $job Job name
 Return string Job file content on success, die on failure

=cut

sub _readJobFile
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    my $fileC = iMSCP::File->new( filename => $self->resolveJob( $job, undef, TRUE ))->get();
    defined $fileC or croak( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
    $fileC;
}

=item _readJobOverrideFile( $job )

 Read the job override file which belongs to the given job if any

 Param string job Job name
 Return string Job override file content on success, die on failure

=cut

sub _readJobOverrideFile
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    my $filepath = eval { $self->resolveJob( $job, 'override', TRUE ); };
    return '' unless defined $filepath;

    my $fileC = iMSCP::File->new( filename => $filepath )->get();
    defined $fileC or croak( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
    $fileC;
}

=item _writeFile( $file, $fileC )

 Write the given job file (job configuration file or job override file)

 Param string $filename File name
 Param string fileC File content
 Return boolean TRUE on success, croak on failure

=cut

sub _writeFile
{
    my ( $self, $file, $fileC ) = @_;

    defined $file or croak( 'Missing or undefined $file parameter' );
    defined $fileC or croak( 'Missing or undefined $fileC parameter' );

    my $jobDir = dirname( $self->resolveJob( basename( $file, '.conf', '.override' )));
    my $filepath = File::Spec->join( $jobDir, $file );

    if ( length $fileC ) {
        $file = iMSCP::File->new( filename => $filepath );
        $file->set( $fileC );
        my $rs ||= $file->save();
        $rs ||= $file->mode( 0644 );
        $rs == 0 or croak( getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' );
    } elsif ( $filepath =~ /\.override$/ && -f $filepath ) {
        iMSCP::File->new( filename => $filepath )->delFile() == 0 or croak(
            getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
        );
    }

    1;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
