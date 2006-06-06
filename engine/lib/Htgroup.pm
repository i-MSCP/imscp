#$Header: /home/cvs/apache-htgroup/lib/Apache/Htgroup.pm,v 1.22 2002/01/27 16:04:34 rbowen Exp $
package Apache::Htgroup;

=head1 NAME

Apache::Htgroup - Manage Apache authentication group files

=head1 SYNOPSIS

  use Apache::Htgroup;
  $htgroup = Apache::Htgroup->load($path_to_groupfile);
  &do_something if $htgroup->ismember($user, $group);
  $htgroup->adduser($user, $group);
  $htgroup->deleteuser($user, $group);
  $htgroup->save;

=head1 DESCRIPTION

Manage Apache htgroup files

Please note that this is I<not> a mod_perl module. Please also note
that there is another module that does similar things
(HTTPD::UserManage) and that this is a more simplistic module,
not doing all the things that one does.

=head2 METHODS

The following methods are provided by this module.

=cut

use strict;
use vars qw($VERSION);
$VERSION = (qw($Revision: 1.22 $))[1];

# sub new, load {{{

=head2 load

    $htgroup = Apache::Htgroup->load($path_to_groupfile);

Returns an Apache::Htgroup object.

=head2 new

    $htgroup = Apache::Htgroup->new();
    $htgroup = Apache::Htgroup->new( $path_to_groupfile );

Creates a new, empty group file. If the specified file already exists,
loads the contents of that file. If no filename is specified, you can
create a group file in memory, and save it later.

=cut

sub new { return load(@_) }

sub load {
    my ( $class, $file ) = @_;
    my $self = bless {
        groupfile => $file,
    }, $class;
    $self->groups;

    return $self;
}

#}}}

# sub adduser {{{

=head2 adduser

    $htgroup->adduser( $username, $group );

Adds the specified user to the specified group.

=cut

sub adduser {
    my $self = shift;
    my ( $user, $group ) = @_;

    return (1) if $self->ismember( $user, $group );
    $self->{groups}->{$group}->{$user} = 1;

    return (1);
}

#}}}

# sub deleteuser {{{

=head2 deleteuser

    $htgroup->deleteuser($user, $group);

Removes the specified user from the group.

=cut

sub deleteuser {
    my $self = shift;
    my ( $user, $group ) = @_;

    delete $self->{groups}->{$group}->{$user};
    return (1);
} # }}}

# sub groups {{{

=head2 groups

    $groups = $htgroup->groups;

Returns a (reference to a) hash of the groups. The key is the name
of the group. Each value is a hashref, the keys of which are the
group members. I suppose there may be some variety of members
method in the future, if anyone thinks that would be useful.

It is expected that this method will not be called directly, and
it is provided as a convenience only. 

Please see the section below about internals for an example
of the data structure.

=cut

sub groups {
    my $self = shift;

    return $self->{groups} if defined $self->{groups};

    $self->reload;

    return $self->{groups};
} # }}}

# sub reload {{{

=head2 reload

     $self->reload;

If you have not already called save(), you can call reload()
and get back to the state of the object as it was loaded from
the original file.

=cut

sub reload {
    my $self = shift;

    if ( $self->{groupfile} ) {

        open( FILE, $self->{groupfile} )
          || die ("Was unable to open group file $self->{groupfile}: $!");
        while ( my $line = <FILE> ) {
            chomp $line;

            #
            # Allow for multiple spaces after the colon.
            # Allow for groups with no users.
            $line =~ /^([^:]+):(\s+)?(.*)?/;
            my $group   = $1;
            my $members = $3;

            #
            # Make sure we keep empty groups
            if(!defined($self->{groups}->{$group}))
            {
                $self->{groups}->{$group} = { };
          }
            foreach my $user( split /\s+/, $members ) {
                $self->{groups}->{$group}->{$user} = 1;
            }
        }
        close FILE;

      } else {
        $self->{groups} = {};
    }
} # }}}

# sub save {{{

=head2 save

    $htgroup->save;
    $htgroup->save($file);

Writes the current contents of the htgroup object back to the
file. If you provide a $file argument, C<save> will attempt to
write to that location.

=cut

sub save {
    my $self = shift;
    my $file = shift || $self->{groupfile};
    my $out;
    my @members;

    open( FILE, ">$file" ) || die ("Was unable to open $file for writing: $!");

    foreach my $group( keys %{ $self->{groups} } ) {

        # Work around the fact that Apache can't handle lines
        # over 8K.
        @members = keys %{ $self->{groups}->{$group} };
      if(!@members) {
              print FILE "${group}: \n";
      }
        while (@members) {
            $out = "$group:";
            while (@members) {
                $out .= " " . shift (@members);
                last if 7500 < length($out);
            }
            print FILE $out, "\n";
        }
    }
    close FILE;

    return (1);
} # }}}

# sub ismember {{{

=head2 ismember

    $foo = $htgroup->ismember($user, $group);

Returns true if the username is in the group, false otherwise

=cut
sub ismember {
    my $self = shift;
    my ( $user, $group ) = @_;

    return ( $self->{groups}->{$group}->{$user} ) ? 1 : 0;
} # }}}

1;

# Documentation {{{

=head1 Internals

Although this was not the case in earlier versions, the internal
data structure of the object looks something like the following:

 $obj = { groupfile => '/path/to/groupfile',
          groups => { group1 => { 'user1' => 1,
                                  'user2' => 1, 
                                  'user3' => 1
                                },
                      group2 => { 'usera' => 1,
                                  'userb' => 1, 
                                  'userc' => 1
                                },
                    }
        };

Note that this data structure is subject to change in the future,
and is provided mostly so that I can remember what the heck I was
thinking when I next have to look at this code.

=head1 Adding groups

A number of folks have asked for a method to add a new group. This
is unnecessary. To add a new group, just start adding users to 
a new group, and the new group will magically spring into existance.

=head1 AUTHOR

Rich Bowen, rbowen@rcbowen.com

=head1 COPYRIGHT

Copyright (c) 2001 Rich Bowen. All rights reserved.
This program is free software; you can redistribute
it and/or modify it under the same terms as Perl itself.

The full text of the license can be found in the
LICENSE file included with this module.

=cut 

# }}}

