package PHP::Var;

use warnings;
use strict;

use JSON;
use Exporter;
use base qw( Exporter );
our @EXPORT_OK = qw( export );

our $Purity = 0;
our $Enclose = 0;
our $ShortArraySyntax = 0;

=head1 NAME

PHP::Var - export variable to PHP's expression.

=head1 VERSION

Version 0.023

=cut

our $VERSION = '0.023';

=head1 SYNOPSIS

    use PHP::Var qw/ export /;

    $var = {foo => 1, bar => 2};

    # export
    $exported = export($var);

    # named variable
    $named = export('name' => $var);

    # enclose variables with '<?php' and  '?>'
    $enclosed  = export($var, enclose => 1);

    # purity print
    $purity  = export($var, purity => 1);

=head1 EXPORT

=head2 export

=head1 FUNCTIONS

=head2 export

    $var = {foo => 1, bar => 2};

    export($var);
    # array('foo'=>1,'bar'=>2,);

    export('name' => $var);
    # $name=array('foo'=>1,'bar'=>2,);

    export($var, enclose => 1);
    # <?php
    # array('foo'=>1,'bar'=>2,);
    # ?>

    export($var, purity => 1);
    # array(
    #    'foo' => 1,
    #    'bar' => 2,
    # );

=head1 Configuration Variables

=head2 $PHP::Var::Purity

When this variable is set, the expression becomes a Pretty print in default.

    {
        local $PHP::Var::Purity = 1;
        export($var);
        # array(
        #    'foo' => 1,
        #    'bar' => 2,
        # );
    }

=head2 $PHP::Var::Enclose

When this variable is set, the expression is enclosed with '<?php' and  '?>' in default.

    {
        local $PHP::Var::Enclose = 1;
        export($var);
        # <?php
        # array('foo'=>1,'bar'=>2,);
        # ?>
    }
=head2 $PHP::Var::ShortArraySyntax

When this variable is set, the expression make use of short array syntax in default.

    {
        local $PHP::Var::ShortArraySyntax = 1;
        export($var);
        # ['foo'=>1,'bar'=>2,];
    }

=cut

sub export
{
    my %opts = (
        purity  => $Purity,
        enclose => $Enclose,
        short   => $ShortArraySyntax
    );

    my @exports = ();
    for ( my $i = 0; $i < scalar( @_ ); $i++ ) {
        if ( ( !ref( $_[$i] ) ) && ( !ref( $_[$i+1] ) ) ) {
            $opts{$_[$i]} = $_[$i+1];
            $i++;
        } else {
            my $key = undef;
            if ( !ref $_[$i] ) {
                $key = $_[$i];
                $i++;
            }
            push( @exports, $key, $_[$i] );
        }
    }

    my $str = '';
    for ( my $i = 0; $i < scalar( @exports ); $i += 2 ) {
        $str .= &_dump( $exports[$i+1], $exports[$i], $opts{'purity'}, $opts{'short'}, 0 ) . ';';
    }

    if ( $opts{'enclose'} ) {
        "<?php\n$str\n?>";
    } else {
        $str;
    }
}

sub _dump
{
    my ( $obj, $key, $purity, $short, $indent ) = @_;

    my $ind = $purity ? "\t" : '';
    my $spc = $purity ? ' ' : '';
    my $nl = $purity ? "\n" : '';
    my $cur_indent = $ind x $indent;
    my $arr_start = $short ? '[' : 'array(';
    my $arr_end = $short ? ']' : ')';
    my $str = '';

    if ( $key ) {
        $str .= '$' . $key . "$spc=$spc";
    }

    if ( ref $obj eq 'HASH' ) {
        if ( %{ $obj } ) {
            $str .= "$arr_start$nl";
            for my $k ( keys( %{ $obj } ) ) {
                $k =~ s/\\/\\\\/go;
                $k =~ s/'/\\'/go;
                $str .= "$cur_indent$ind'" . $k . "'$spc=>$spc"
                    . &_dump( $obj->{$k}, undef, $purity, $short, $indent+1 ) . ",$nl";
            }
            $str .= "$cur_indent$arr_end";
        } else {
            $str .= "$arr_start$arr_end";
        }
    } elsif ( ref $obj eq 'ARRAY' ) {
        if ( @{ $obj } ) {
            $str .= "$arr_start$nl";
            for ( my $i = 0; $i < scalar( @{ $obj } ); $i++ ) {
                $str .= "$cur_indent$ind" . &_dump( $obj->[$i], undef, $purity, $short, $indent+1 ) . ",$nl";
            }
            $str .= "$cur_indent$arr_end";
        } else {
            $str .= "$arr_start$arr_end";
        }
    } elsif ( ref $obj eq 'SCALAR' ) {
        ${ $obj } =~ s/\\/\\\\/go;
        ${ $obj } =~ s/'/\\'/go;

        if ( defined ${ $obj } ) {
            if ( JSON::is_bool( ${ $obj } ) ) {
                $str .= ${ $obj } ? 'true' : 'false';
            } elsif ( ${ $obj } =~ /^-?(0|[1-9]\d{0,8})$/ ) {
                $str .= ${ $obj };
            } else {
                $str .= "'${ $obj }'";
            }
        } else {
            $str .= 'null';
        }
    } elsif ( defined( $obj ) ) {
        $obj =~ s/\\/\\\\/go;
        $obj =~ s/'/\\'/go;

        if ( JSON::is_bool( $obj ) ) {
            $str .= $obj ? 'true' : 'false';
        } elsif ( $obj =~ /^-?(0|[1-9]\d{0,8})$/ ) {
            $str .= $obj;
        } else {
            $str .= "'$obj'";
        }
    } else {
        $str .= 'null';
    }

    $str;
}

=head1 NOTES

=over 4

=item *

PHP::Var::export cannot export the blessed object as data that can be restored.

=back

=head1 AUTHOR

Laurent Derclercq, C<< <l.declercq at nuxwin.com>  >>
Taku Amano, C<< <taku at toi-planning.net> >>

=head1 SEE ALSO

L<PHP::Session::Serializer::PHP>

=head1 SUPPORT

You can find documentation for this module with the perldoc command.

    perldoc PHP::Var

=head1 COPYRIGHT & LICENSE

Copyright 2009 Taku Amano.

This program is free software; you can redistribute it and/or modify it
under the terms of either: the GNU General Public License as published
by the Free Software Foundation; or the Artistic License.

See http://dev.perl.org/licenses/ for more information.


=cut

1; # End of PHP::Var
