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
use Hash::Util::FieldHash 'fieldhash';

# Private variables
my $VERSION;
my $START_ON = qr/^\s*start\s+on/;
my $COMMENTED_START_ON = qr/^\s*#+\s*start\s+on/;
my $MANUAL = qr/^\s*manual\s*$/;

# Paths where job files must be searched
fieldhash my %paths;

# Commands used in that package
my %commands = (
	'start' => '/sbin/start --system',
	'stop' => '/sbin/stop --system',
	'restart' => '/sbin/restart --system',
	'reload' => '/sbin/reload --system',
	'status' => '/sbin/status --system',
	'initctl' => '/sbin/initctl --system'
);

=head1 DESCRIPTION

 Base service provider for `upstart` jobs.

 See:
  http://upstart.ubuntu.com/

=head1 PUBLIC METHODS

=over 4

=item isEnabled($service)

 Does the given service is enabled?

 Return bool TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
	my ($self, $service) = @_;

	my $jobFileContent = $self->_readJobFile($service);

	if($self->_versionIsPre067()) {
		return $self->_isEnabledPre067($jobFileContent);
	}

	if($self->_versionIsPre090()) {
		return $self->_isEnabledPre090($jobFileContent);
	}

	if($self->_versionIsPost090()) {
		return $self->_isEnabledPost090($jobFileContent, $self->_readJobOverrideFile($service));
	}

	0;
}

=item enable($service)

 Enable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
	my ($self, $service) = @_;

	my $jobFileContent = $self->_readJobFile($service);

	if($self->_versionIsPre090()) {
		return $self->_enablePre090($service, $jobFileContent);
	}

	$self->_enablePost090($service, $jobFileContent, $self->_readJobOverrideFile($service));
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
	my ($self, $service) = @_;

	if($self->_versionIsPre067()) {
		return $self->_disablePre067($service, $self->_readJobFile($service));
	}

	if($self->_versionIsPre090()) {
		return $self->_disablePre090($service, $self->_readJobFile($service));
	}

	if($self->_versionIsPost090()) {
		return $self->_disablePost090($service, $self->_readJobOverrideFile($service));
	}

	0;
}

=item remove($service)

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
	my ($self, $service) = @_;

	return 0 unless $self->stop($service);

	local $@;

	for my $jobFileType('conf', 'override') {
		if((my $filepath = eval { $self->getJobFilePath($service, $jobFileType); })) {
			return if iMSCP::File->new( filename => $filepath )->delFile();
		}
	}

	1;
}

=item start($service)

 Start the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub start
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		unless($self->isRunning($service)) {
			return $self->_exec($commands{'start'}, $service) == 0;
		}

		return 1;
	}

	$self->SUPER::start($service);
}

=item stop($service)

 Stop the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub stop
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		if($self->isRunning($service)) {
			return $self->_exec($commands{'stop'}, $service) == 0;
		}

		return 1;
	}

	$self->SUPER::stop($service);
}

=item restart($service)

 Restart the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub restart
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		if($self->isRunning($service)) {
			return $self->_exec($commands{'restart'}, $service) == 0;
		}

		return $self->start($service);
	}

	$self->SUPER::restart($service);
}

=item reload($service)

 Reload the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub reload
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		if($self->isRunning($service)) {
			return $self->_exec($commands{'reload'}, $service) == 0;
		}

		return $self->start($service);
	}

	$self->SUPER::reload($service);

}

=item isRunning($service)

 Does the given service is running?

 Param string $service Service name
 Return bool TRUE if the given service is running, FALSE on failure

=cut

sub isRunning
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		execute("$commands{'status'} $service", \ my $stdout, \ my $stderr);
		return $stdout =~ m%start/%;
	}

	$self->SUPER::isRunning($service);
}

=item getJobFilePath($service [ , $jobFileType = 'conf' ])

 Get full path of the job configuration file or job override file which belongs to the given service

 Param string $service Service name
 Param string $jobFileType OPTIONAL Job file type ('conf'|'override') - Default to 'conf'
 Return string job file path on success, die on failure

=cut

sub getJobFilePath
{
	my ($self, $service, $jobFileType) = @_;

	$jobFileType ||= 'conf';
	$self->_searchJobFile($service, $jobFileType);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Provider::Service::Upstart

=cut

sub _init
{
	my $self = shift;

	delete $ENV{'UPSTART_SESSION'}; # See IP-1514

	$paths{$self} = [ '/etc/init' ];
	$self->SUPER::_init();
}

=item _getVersion()

 Get upstart version

 Return string Upstart version

=cut

sub _getVersion
{
	unless($VERSION) {
		($VERSION) = `initctl --version` =~ /initctl \(upstart\s+([^\)]*)\)/;
	}

	$VERSION;
}

=item _isUpstart($service)

 Does the given service is managed by an upstart job?

 Param string $service Service name
 Return bool TRUE if the given service is managed by an upstart job, FALSE otherwise

=cut

sub _isUpstart
{
	my ($self, $service) = @_;

	local $@;
	eval { $self->_searchJobFile($service); };
}

=item _versionIsPre067()

 Does the upstart version is pre 0.6.7?

 Return bool TRUE if upstart version is pre 0.6.7, FALSE otherwise

=cut

sub _versionIsPre067
{
	my $self = shift;

	version->parse($self->_getVersion()) < version->parse('0.6.7');
}

=item _versionIsPre090()

 Does the upstart version is pre 0.9.0?

 Return bool TRUE if upstart version is pre 0.9.0, FALSE otherwise

=cut

sub _versionIsPre090
{
	my $self = shift;

	version->parse($self->_getVersion()) < version->parse('0.9.0');
}

=item _versionIsPost090()

 Does the upstart version is post 0.9.0?

 Return bool TRUE if upstart version is post 0.9.0, FALSE otherwise

=cut

sub _versionIsPost090
{
	my $self = shift;

	version->parse($self->_getVersion()) >= version->parse('0.9.0');
}

=item _isEnabledPre067($jobFileContent)

 Does the given job is enabled for upstart versions < 0.6.7?

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

 Does the given job is enabled for upstart versions < 0.9.0?

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
		if(/$START_ON/) {
			$enabled = 1;
		} elsif(/$MANUAL/) {
			$enabled = 0;
		}
	}

	$enabled;
}

=item _isEnabledPost090($jobFileContent, $jobOverrideFileContent)

 Does the given job is enabled for upstart versions >= 0.9.0?

 Param string $jobFileContent job file content
 Param string $jobOverrideFileContent job override file content
 Return bool TRUE if the given job is enabled, FALSE otherwise

=cut

sub _isEnabledPost090
{
	my ($self, $jobFileContent, $jobOverrideFileContent) = @_;

	# Upstart versions >= 0.9.0 has `manual` stanzas and override files. Thus, we check to see if an uncommented `start on` or `manual`
	# stanza is the last one in the conf file and any override files. The last one in the file wins.
	my $enabled = 0;

	for($jobFileContent, $jobOverrideFileContent) {
		next unless defined;

		for(split /^/) {
			if(/$START_ON/) {
				$enabled = 1;
			} elsif(/$MANUAL/) {
				$enabled = 0;
			}
		}
	}

	$enabled;
}

=item _enablePre090($service, $jobFileContent)

 Enable the given service for upstart versions < 0.9.0

 Param string $service Service name
 Param string $jobFileContent job file content
 Return bool TRUE on success, die on failure

=cut

sub _enablePre090
{
	my ($self, $service, $jobFileContent) = @_;

	$jobFileContent = $self->_removeManualStanza($jobFileContent);

	unless($self->_isEnabledPre090($jobFileContent)) {
		if($jobFileContent =~ /$COMMENTED_START_ON/) {
			$jobFileContent = $self->_uncommentStartOnStanza($jobFileContent);
		} else {
			$jobFileContent = $self->_addDefaultStartOnStanza($jobFileContent);
		}
	} else {
		1;
	}
}

=item _enablePost090($service, $jobFileContent, $jobOverrideFileContent)

 Enable the given service for upstart versions >= 0.9.0

 Param string $service Service name
 Param string $jobFileContent job file content
 Param string $jobOverrideFileContent job override file content
 Return bool TRUE on success, die on failure

=cut

sub _enablePost090
{
	my ($self, $service, $jobFileContent, $jobOverrideFileContent) = @_;

	$jobOverrideFileContent = $self->_removeManualStanza($jobOverrideFileContent);

	unless($self->_isEnabledPost090($jobFileContent, $jobOverrideFileContent)) {
		if($jobFileContent =~ /$START_ON/) {
			$jobOverrideFileContent .= $self->_extractStartOnStanza($jobFileContent);
		} else {
			$jobOverrideFileContent .= "\nstart on runlevel [2345]";
		}

		$self->_writeFile("$service.override", $jobOverrideFileContent);
	} else {
		1;
	}
}

=item _disablePre067($service, $jobFileContent)

 Disable the given service for upstart versions < 0.6.7

 Param string $service Service name
 Param string $jobFileContent job file content
 Return bool TRUE on success, die on failure

=cut

sub _disablePre067
{
	my ($self, $service, $jobFileContent) = @_;

	$jobFileContent = $self->_commentStartOnStanza($jobFileContent);
	$self->_writeFile("$service.conf", $jobFileContent);
}

=item _disablePre090($service, $jobFileContent)

 Disable the given service for upstart versions < 0.9.0

 Param string $service Service name
 Param string $jobFileContent job file content
 Return bool TRUE on success, die on failure

=cut

sub _disablePre090
{
	my ($self, $service, $jobFileContent) = @_;

	$self->_writeFile("$service.conf", $self->_ensureDisabledWithManualStanza($jobFileContent));
}

=item _disablePost090($service, $jobOverrideFileContent)

 Disable the given service for upstart versions >= 0.9.0

 Param string $service Service name
 Param string $jobOverrideFileContent job $jobOverrideFileContent file content
 Return bool TRUE on success, die on failure

=cut

sub _disablePost090
{
	my ($self, $service, $jobOverrideFileContent) = @_;

	$self->_writeFile("$service.override", $self->_ensureDisabledWithManualStanza($jobOverrideFileContent));
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

	join '',  map {
		if(/$START_ON/ || $parentheses > 0) {
			$parentheses += $self->_countUnbalancedParentheses($self->_removeTrailingComments($_));
			'#' . $_;
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

	join '', map {
		if(/$COMMENTED_START_ON/ || $parentheses > 0) {
			# If there are more opening parentheses than closing parentheses, we need to comment out a multiline
			# 'start on' stanza
			$parentheses += $self->_countUnbalancedParentheses($self->_removeTrailingCommentsFromCommentedLine($_));
			$self->_uncomment($_);
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

	join '', map {
		if(/$START_ON/ || $parentheses > 0) {
			$parentheses += $self->_countUnbalancedParentheses($self->_removeTrailingComments($_));
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
	"$_[1]g\nstart on runlevel [2345]";
}

=item _ensureDisabledWithManualStanza($text)

 Ensure that the given text contains the upstart `manual` stanza

 Param string $text Text to process
 Return string Text with upstart `manual` stanza

=cut

sub _ensureDisabledWithManualStanza
{
	my ($self, $text) = @_;

	$self->_removeManualStanza($text) . "\nmanual";
}

=item _searchJobFile($service ,$jobFileType)

 Search the job configuration file or job override file which belongs to the given service in all available paths

 Param string $service Service name
 Param string $jobFileType Job file type ('conf'|'override')
 Return string Job file path on success, die on failure

=cut

sub _searchJobFile
{
	my ($self, $service, $jobFileType) = @_;

	my $jobFile = "$service." . ($jobFileType || 'conf');

	for my $path(@{$paths{$self}}) {
		my $filepath = File::Spec->join($path, $jobFile);
		return $filepath if -f $filepath;
	}

	die(sprintf('Could not find the upstart %s job file', $jobFile));
}

=item _readJobFile($service)

 Read the job file which belongs to the given service

 Param string $service Service name
 Return string Job file content on success, die on failure

=cut

sub _readJobFile
{
	my ($self, $service) = @_;

	my $filepath = $self->getJobFilePath($service);
	iMSCP::File->new( filename => $filepath )->get() or die(sprintf('Could not read %s file', $filepath));
}

=item _readJobOverrideFile($service)

 Read the job override file which belongs to the given service

 Param string $service Service name
 Return string Job override file content on success, die on failure

=cut

sub _readJobOverrideFile
{
	my ($self, $service) = @_;

	if((my $filepath = eval { $self->getJobFilePath($service, 'override'); })) {
		return iMSCP::File->new( filename => $filepath )->get() or die(sprintf('Could not read %s file', $filepath));
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

	my $jobDir = dirname($self->getJobFilePath(fileparse($filename, qr/\.[^.]*/)));
	my $filepath = File::Spec->join($jobDir, $filename);
	my $file = iMSCP::File->new( filename => $filepath );

	$file->set($fileContent) == 0 && $file->save() == 0 && $file->mode(0644) == 0 or die(sprintf(
		'Could not write %s file', $filepath
	));
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
