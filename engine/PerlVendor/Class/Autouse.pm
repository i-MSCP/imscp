package Class::Autouse;

# See POD at end of file for documentation

use 5.006;
use strict;
no strict 'refs'; # We _really_ abuse refs :)
use UNIVERSAL ();

# Load required modules
# Luckily, these are so common they are basically free
use Carp            ();
use Exporter        ();
use File::Spec 0.80 ();
use List::Util 1.18 ();
use Scalar::Util    ();

# Globals
use vars qw{ $VERSION @ISA $DB $DEBUG };
use vars qw{ $DEVEL $SUPERLOAD $NOSTAT $NOPREBLESS $STATICISA   }; # Load environment
use vars qw{ %SPECIAL %LOADED %BAD %TRIED_CLASS %TRIED_METHOD   }; # Special cases
use vars qw{ @LOADERS @SUGAR $HOOKS $ORIGINAL_CAN $ORIGINAL_ISA }; # Working information

# Handle optimisation switches via constants to allow debugging and
# similar functions to be optimised out at compile time if not in use.
BEGIN {
    $DB    = 0 unless defined &DB::DB;
    $DEBUG = 0 unless defined $DEBUG;
}
use constant DB    => !! $DB;
use constant DEBUG => !! $DEBUG;
print "Class::Autouse -> Debugging Activated.\n" if DEBUG;

# Compile-time Initialisation and Optimisation
BEGIN {
    $VERSION = '2.01';

    # Become an exporter so we don't get complaints when we act as a pragma.
    # I don't fully understand the reason for this, but it works and I can't
    # recall how to replicate the problem, so leaving it in to avoid any
    # possible reversion. Besides, so many things use Exporter it should
    # be practically free to do this.
    @ISA = qw{ Exporter };

    # We always start with the superloader off
    $SUPERLOAD = 0;

    # When set, disables $obj->isa/can where $obj is blessed before its class is loaded
    # Things will operate more quickly when set, but this breaks things if you're
    # unserializing objects from Data::Dumper, etc., and relying on this module to
    # load the related classes on demand.
    $NOPREBLESS = 0;

    # Disable stating for situations where modules are on remote disks
    $NOSTAT = 0;

    # AUTOLOAD hook counter
    $HOOKS = 0;

    # ERRATA
    # Special classes are internal and should be left alone.
    # Loaded modules are those already loaded by us.
    # Bad classes are those that are incompatible with us.
    %BAD = map { $_ => 1 } qw{
        IO::File
        };

    %SPECIAL = map { $_ => 1 } qw{
        CORE  main UNIVERSAL
        ARRAY HASH SCALAR REF GLOB
        };

    %LOADED = map { $_ => 1 } qw{
        UNIVERSAL
        Carp
        Exporter
        File::Spec
        List::Util
        Scalar::Util
        Class::Autouse
        };

    # "Have we tried to autoload a class before?"
    # Per-class loop protection and improved shortcutting.
    # Defaults to specials+preloaded to prevent attempting them.
    %TRIED_CLASS = ( %SPECIAL, %LOADED );

    # "Have we tried to autoload a method before?"
    # Per-method loop protection and improved shortcutting
    %TRIED_METHOD = ();

    # Storage for dynamic loaders (regular and sugar)
    @LOADERS = ();
    @SUGAR   = ();

    # We play with UNIVERSAL:: functions, so save backup copies
    $ORIGINAL_CAN = \&UNIVERSAL::can;
    $ORIGINAL_ISA = \&UNIVERSAL::isa;
}

#####################################################################
# Configuration and Setting up

# Developer mode flag.
# Cannot be turned off once turned on.
sub devel {
    _debug(\@_, 1) if DEBUG;

    # Enable if not already
    return 1 if $DEVEL++;

    # Load any unloaded modules.
    # Most of the time there should be nothing here.
    foreach my $class ( grep { $INC{$_} eq 'Class::Autouse' } keys %INC ) {
        $class =~ s/\//::/;
        $class =~ s/\.pm$//i;
        Class::Autouse->load($class);
    }
}

# Happy Fun Super Loader!
# The process here is to replace the &UNIVERSAL::AUTOLOAD sub
# ( which is just a dummy by default ) with a flexible class loader.
sub superloader {
    _debug(\@_, 1) if DEBUG;

    # Shortcut if needed
    return 1 if $SUPERLOAD++;

    # Enable the global hooks
    _GLOBAL_HOOKS();

    return 1;
}

sub sugar {
    # Operate as a function or a method
    shift if $_[0] eq 'Class::Autouse';

    # Ignore calls with no arguments
    return 1 unless @_;

    _debug(\@_) if DEBUG;

    foreach my $callback ( grep { $_ } @_ ) {
        # Handle a callback or regex
        unless ( ref $callback eq 'CODE' ) {
            die(
                __PACKAGE__
                    . ' takes a code reference for syntactic sugar handlers'
                    . ": unexpected value $callback has type "
                    . ref($callback)
            );
        }
        push @SUGAR, $callback;

        # Enable global hooking
        _GLOBAL_HOOKS();
    }

    return 1;
}

# The main autouse sub
sub autouse {
    # Operate as a function or a method
    shift if $_[0] eq 'Class::Autouse';

    # Ignore calls with no arguments
    return 1 unless @_;

    _debug(\@_) if DEBUG;

    foreach my $class ( grep { $_ } @_ ) {
        if ( ref $class ) {
            unless ( ref $class eq 'Regexp' or ref $class eq 'CODE') {
                die( __PACKAGE__
                        . ' can autouse explicit class names, or take a regex or subroutine reference'
                        . ": unexpected value $class has type "
                        . ref($class)
                );
            }
            push @LOADERS, $class;

            # Enable the global hooks
            _GLOBAL_HOOKS();

            # Reset shortcut cache, since we may have previously
            # tried a class and failed, which could now work
            %TRIED_CLASS = ( %SPECIAL, %LOADED );
            next;
        }

        # Control flag handling
        if ( substr($class, 0, 1) eq ':' ) {
            if ( $class eq ':superloader' ) {
                # Turn on the superloader
                Class::Autouse->superloader;
            } elsif ( $class eq ':devel' ) {
                # Turn on devel mode
                Class::Autouse->devel(1);
            } elsif ( $class eq ':nostat' ) {
                # Disable stat checks
                $NOSTAT = 1;
            } elsif ( $class eq ':noprebless') {
                # Disable support for objects blessed before their class module is loaded
                $NOPREBLESS = 1;
            } elsif ( $class eq ':staticisa') {
                # Expect that @ISA won't change after loading
                # This allows some performance tweaks
                $STATICISA = 1;
            }
            next;
        }

        # Load now if in devel mode, or if its a bad class
        if ( $DEVEL || $BAD{$class} ) {
            Class::Autouse->load($class);
            next;
        }

        # Does the file for the class exist?
        my $file = _class_file($class);
        next if exists $INC{$file};
        unless ( $NOSTAT or _file_exists($file) ) {
            my $inc = join ', ', @INC;
            _cry("Can't locate $file in \@INC (\@INC contains: $inc)");
        }

        # Don't actually do anything if the superloader is on.
        # It will catch all AUTOLOAD calls.
        next if $SUPERLOAD;

        # Add the AUTOLOAD hook and %INC lock to prevent 'use'ing
        *{"${class}::AUTOLOAD"} = \&_AUTOLOAD;
        $INC{$file} = 'Class::Autouse';

        # When we add the first hook, hijack UNIVERSAL::can/isa
        _UPDATE_HOOKS() unless $HOOKS++;
    }

    return 1;
}

# Import behaves the same as autouse
sub import {
    shift->autouse(@_);
}

#####################################################################
# Explicit Actions

# Completely load a class ( The class and all its dependencies ).
sub load {
    _debug(\@_, 1) if DEBUG;

    my $class = $_[1] or _cry('No class name specified to load');
    return 1 if $LOADED{$class};

    my @search = _super( $class, \&_load );

    # If called an an array context, return the ISA tree.
    # In scalar context, just return true.
    wantarray ? @search : 1;
}

# Is a particular class installed in out @INC somewhere
# OR is it loaded in our program already
sub class_exists {
    _debug(\@_, 1) if DEBUG;
    _namespace_occupied($_[1]) or _file_exists($_[1]);
}

# A more general method to answer the question
# "Can I call a method on this class and expect it to work"
# Returns undef if the class does not exist
# Returns 0 if the class is not loaded ( or autouse'd )
# Returns 1 if the class can be used.
sub can_call_methods {
    _debug(\@_, 1) if DEBUG;
    _namespace_occupied($_[1]) or exists $INC{_class_file($_[1])};
}

# Recursive methods currently only work withing the scope of the single @INC
# entry containing the "top" module, and will probably stay this way

# Autouse not only a class, but all others below it.
sub autouse_recursive {
    _debug(\@_, 1) if DEBUG;

    # Just load if in devel mode
    return Class::Autouse->load_recursive($_[1]) if $DEVEL;

    # Don't need to do anything if the super loader is on
    return 1 if $SUPERLOAD;

    # Find all the child classes, and hand them to the autouse method
    Class::Autouse->autouse( $_[1], _children($_[1]) );
}

# Load not only a class and all others below it
sub load_recursive {
    _debug(\@_, 1) if DEBUG;

    # Load the parent class, and its children
    foreach ( $_[1], _children($_[1]) ) {
        Class::Autouse->load($_);
    }

    return 1;
}

#####################################################################
# Symbol Table Hooks

# These get hooked to various places on the symbol table,
# to enable the autoload functionality

# Linked to each individual class via the symbol table
sub _AUTOLOAD {
    _debug(\@_, 0, ", AUTOLOAD = '$Class::Autouse::AUTOLOAD'") if DEBUG;

    # Loop detection (just in case)
    my $method = $Class::Autouse::AUTOLOAD or _cry('Missing method name');
    _cry("Undefined subroutine &$method called") if ++$TRIED_METHOD{$method} > 10;

    # Don't bother with special classes
    my ($class, $function) = $method =~ m/^(.*)::(.*)\z/s;
    _cry("Undefined subroutine &$method called") if $SPECIAL{$class};

    # Load the class and it's dependancies, and get the search path
    my @search = Class::Autouse->load($class);

    # Find and go to the named method
    my $found = List::Util::first {
            defined *{"${_}::$function"}{CODE}
        } @search;
    goto &{"${found}::$function"} if $found;

    # Check for package AUTOLOADs
    foreach my $c ( @search ) {
        if ( defined *{"${c}::AUTOLOAD"}{CODE} ) {
            # Simulate a normal autoload call
            ${"${c}::AUTOLOAD"} = $method;
            goto &{"${c}::AUTOLOAD"};
        }
    }

    # Can't find the method anywhere. Throw the same error Perl does.
    _cry("Can't locate object method \"$function\" via package \"$class\"");
}

# This is a special version of the above for use in UNIVERSAL
# It does the :superloader, and/or also any regex or callback (code ref) loaders
sub _UNIVERSAL_AUTOLOAD {
    _debug(\@_, 0, ", \$AUTOLOAD = '$Class::Autouse::AUTOLOAD'") if DEBUG;

    # Loop detection ( Just in case )
    my $method = $Class::Autouse::AUTOLOAD or _cry('Missing method name');
    _cry("Undefined subroutine &$method called") if ++$TRIED_METHOD{ $method } > 10;

    # Don't bother with special classes
    my ($class, $function) = $method =~ m/^(.*)::(.*)\z/s;
    _cry("Undefined subroutine &$method called") if $SPECIAL{$class};

    my @search;
    if ( $SUPERLOAD ) {
        # Only try direct loading of the class if the superloader is active.
        # This might be installed in universal for either the superloader, special loaders, or both.

        # Load the class and it's dependancies, and get the search path
        @search = Class::Autouse->load($class);
    }

    unless ( @search ) {
        # The special loaders will attempt to dynamically instantiate the class.
        # They will not fire if the superloader is turned on and has already loaded the class.
        if ( _try_loaders($class, $function, @_) ) {
            my $fref = $ORIGINAL_CAN->($class, $function);
            if ( $fref ) {
                goto $fref;
            } else {
                @search = _super($class);
            }
        }
    }

    # Find and go to the named method
    my $found = List::Util::first { defined *{"${_}::$function"}{CODE} } @search;
    goto &{"${found}::$function"} if $found;

    # Check for package AUTOLOADs
    foreach my $c ( @search ) {
        if ( defined *{"${c}::AUTOLOAD"}{CODE} ) {
            # Simulate a normal autoload call
            ${"${c}::AUTOLOAD"} = $method;
            goto &{"${c}::AUTOLOAD"};
        }
    }

    for my $callback ( @SUGAR ) {
        my $rv = $callback->( $class, $function, @_ );
        goto $rv if $rv;
    }

    # Can't find the method anywhere. Throw the same error Perl does.
    _cry("Can't locate object method \"$function\" via package \"$class\"");
}

# This just handles the call and does nothing.
# It prevents destroy calls going through to the AUTOLOAD hooks.
sub _UNIVERSAL_DESTROY {
    _debug(\@_) if DEBUG;
}

sub _isa {
    # Optional performance hack
    goto $ORIGINAL_ISA if ref $_[0] and $NOPREBLESS;

    # Load the class, unless we are sure it is already
    my $class = ref $_[0] || $_[0] || return undef;
    unless ( $TRIED_CLASS{$class} or $LOADED{$class} ) {
        _preload($_[0]);
    }

    goto &{$ORIGINAL_ISA};
}

# This is the replacement for UNIVERSAL::can
sub _can {
    # Optional performance hack
    goto $ORIGINAL_CAN if ref $_[0] and $NOPREBLESS;

    # Load the class, unless we are sure it is already
    my $class = ref $_[0] || $_[0] || return undef;
    unless ( $TRIED_CLASS{$class} or $LOADED{$class} ) {
        _preload($_[0]);
    }

    goto &{$ORIGINAL_CAN};
}

#####################################################################
# Support Functions

sub _preload {
    _debug(\@_) if DEBUG;

    # Does it look like a package?
    my $class = ref $_[0] || $_[0];
    unless ( $class and $class =~ /^[^\W\d]\w*(?:(?:\'|::)[^\W]\w*)*$/o ) {
        return $LOADED{$class} = 1;
    }

    # Do we try to load the class
    my $load = 0;
    my $file = _class_file($class);
    if ( defined $INC{$file} and $INC{$file} eq 'Class::Autouse' ) {
        # It's an autoused class
        $load = 1;
    } elsif ( ! $SUPERLOAD ) {
        # Superloader isn't on, don't load
        $load = 0;
    } elsif ( _namespace_occupied($class) ) {
        # Superloader is on, but there is something already in the class
        # This can't be the autouse loader, because we would have caught
        # that case already.
        $load = 0;
    } else {
        # The rules of the superloader say we assume loaded unless we can
        # tell otherwise. Thus, we have to have a go at loading.
        $load = 1;
    }

    # If needed, load the class and all its dependencies.
    Class::Autouse->load($class) if $load;

    unless ( $LOADED{$class} ) {
        _try_loaders($class);
        unless ( $LOADED{$class} ) {
            if ( _namespace_occupied($class) ) {
                # The class is not flagged as loaded by autouse, but exists
                # to ensure its ancestry is loaded before calling $orig
                $LOADED{$class} = 1;
                _load_ancestors($class);
            }
        }
    }

    return 1;
}

sub _try_loaders {
    _debug(\@_, 0) if DEBUG;
    my ($class, $function, @optional_args) = @_;
    # The function and args are only present to help callbacks whose main goal is to
    # do "syntactic sugar" instead of really writing a class

    # This allows us to shortcut out of re-checking a class
    $TRIED_CLASS{$class}++;

    if ( _namespace_occupied($class) ) {
        $LOADED{$class} = 1;
        _load_ancestors($class);
        return 1;
    }

    # Try each of the special loaders, if there are any.
    for my $loader ( @LOADERS ) {
        my $ref = ref($loader);
        if ( $ref ) {
            if ( $ref eq "Regexp" ) {
                next unless $class =~ $loader;
                my $file = _class_file($class);
                next unless grep { -e $_ . '/' . $file } @INC;
                local $^W = 0;
                local $@;
                eval "use $class";
                die "Class::Autouse found module $file for class $class matching regex '$loader',"
                        . " but it failed to compile with the following error: $@" if $@;
            } elsif ( $ref eq "CODE" ) {
                unless ( $loader->( $class,$function,@optional_args ) ) {
                    next;
                }
            } else {
                die "Unexpected loader.  Expected qr//, sub{}, or class name string."
            }
            $LOADED{$class} = 1;
            _load_ancestors($class);
            return 1;
        } else {
            die "Odd loader $loader passed to " . __PACKAGE__;
        }
    }

    return;
}

# This is called after any class is hit by load/preload to ensure that parent classes are also loaded
sub _load_ancestors {
    _debug(\@_, 0) if DEBUG;
    my $class = $_[0];
    my ($this_class,@ancestors) = _super($class);
    for my $ancestor ( @ancestors ) {
        # this is a bit ugly, _preload presumes either isa or can is being called,
        # and does a goto at the end of it, we just want the core logic, not the redirection
        # so we pass undef as the subref parameter
        _preload($ancestor);
    }
    if ( $STATICISA ) {
        # Optional performance optimization.
        # After we have the entire ancestry,
        # set the greatest grandparent's can/isa to the originals.
        # This keeps the versions in this module from being used where they're not needed.
        my $final_parent = $ancestors[-1] || $this_class;
        no strict 'refs';
        *{ $final_parent . '::can'} = $ORIGINAL_CAN;
        *{ $final_parent . '::isa'} = $ORIGINAL_ISA;
    }
    return 1;
}

# This walks the @ISA tree, optionally calling a subref on each class
# and returns the inherited classes in a list, including $class itself.
sub _super {
    _debug(\@_) if DEBUG;
    my $class  = shift;
    my $load   = shift;
    my @stack  = ( $class );
    my %seen   = ( UNIVERSAL => 1 );
    my @search = ();

    while ( my $c = shift @stack ) {
        next if $seen{$c}++;

        # This may load the class in question, so
        # we call it before checking @ISA.
        if ( $load and not $LOADED{$c} ) {
            $load->($c);
        }

        # Add the class to the search list,
        # and add the @ISA to the load stack.
        push @search, $c;
        unshift @stack, @{"${c}::ISA"};
    }

    return @search;
}

# Load a single class
sub _load ($) {
    _debug(\@_) if DEBUG;

    # Don't attempt to load special classes
    my $class = shift or _cry('Did not specify a class to load');
    $TRIED_CLASS{$class}++;

    return 1 if $SPECIAL{$class};

    # Run some checks
    my $file = _class_file($class);
    if ( defined $INC{$file} ) {
        # If the %INC lock is set to any other value, the file is
        # already loaded. We do not need to do anything.
        if ( $INC{$file} ne 'Class::Autouse') {
            return $LOADED{$class} = 1;
        }

        # Because we autoused it earlier, we know the file for this
        # class MUST exist.
        # Removing the AUTOLOAD hook and %INC lock is all we have to do
        delete ${"${class}::"}{'AUTOLOAD'};
        delete $INC{$file};

    } elsif ( not _file_exists($file) ) {
        # We might still be loaded, if the class was defined
        # in some other module without it's own file.
        if ( _namespace_occupied($class) ) {
            return $LOADED{$class} = 1;
        }

        # Not loaded and no file either.
        # Try to generate the class instead.
        if ( _try_loaders($class) ) {
            return 1;
        }

        # We've run out of options, it just doesn't exist
        my $inc = join ', ', @INC;
        _cry("Can't locate $file in \@INC (\@INC contains: $inc)");
    }

    # Load the file for this class
    print _depth(1) . "  Class::Autouse::load -> Loading in $file\n" if DEBUG;
    eval {
        CORE::require($file);
    };
    _cry($@) if $@;

    # Give back UNIVERSAL::can/isa if there are no other hooks
    --$HOOKS or _UPDATE_HOOKS();

    $LOADED{$class} = 1;
    _load_ancestors($class);
    return 1;
}

# Find all the child classes for a parent class.
# Returns in the list context.
sub _children ($) {
    _debug(\@_) if DEBUG;

    # Find where it is in @INC
    my $base_file = _class_file(shift);
    my $inc_path  = List::Util::first {
            -f File::Spec->catfile($_, $base_file)
        } @INC or return;

    # Does the file have a subdirectory
    # i.e. Are there child classes
    my $child_path      = substr( $base_file, 0, length($base_file) - 3 );
    my $child_path_full = File::Spec->catdir( $inc_path, $child_path );
    return 0 unless -d $child_path_full and -r _;

    # Main scan loop
    local *FILELIST;
    my ($dir, @files, @modules) = ();
    my @queue = ( $child_path );
    while ( $dir = pop @queue ) {
        my $full_dir = File::Spec->catdir($inc_path, $dir);

        # Read in the raw file list
        # Skip directories we can't open
        opendir( FILELIST, $full_dir ) or next;
        @files = readdir FILELIST;
        closedir FILELIST;

        # Iterate over them
        @files = map  { File::Spec->catfile($dir, $_) } # Full relative path
            grep { ! /^\./ } @files;               # Ignore hidden files
        foreach my $file ( @files ) {
            my $full_file = File::Spec->catfile($inc_path, $file);

            # Add to the queue if its a directory we can descend
            if ( -d $full_file and -r _ ) {
                push @queue, $file;
                next;
            }

            # We only want .pm files we can read
            next unless substr( $file, length($file) - 3 ) eq '.pm';
            next unless -f _;

            push @modules, $file;
        }
    }

    # Convert the file names into modules
    map { join '::', File::Spec->splitdir($_) }
        map { substr($_, 0, length($_) - 3)       } @modules;
}

#####################################################################
# Private support methods

# Does a class or file exists somewhere in our include path. For
# convenience, returns the unresolved file name ( even if passed a class )
sub _file_exists ($) {
    _debug(\@_) if DEBUG;

    # What are we looking for?
    my $file = shift or return undef;
    return undef if $file =~ m/(?:\012|\015)/o;

    # If provided a class name, convert it
    $file = _class_file($file) if $file =~ /::/o;

    # Scan @INC for the file
    foreach ( @INC ) {
        next if ref $_ eq 'CODE';
        return $file if -f File::Spec->catfile($_, $file);
    }

    undef;
}

# Is a namespace occupied by anything significant
sub _namespace_occupied ($) {
    _debug(\@_) if DEBUG;

    # Handle the most likely case
    my $class = shift or return undef;
    return 1 if @{"${class}::ISA"};

    # Get the list of glob names, ignoring namespaces
    foreach ( keys %{"${class}::"} ) {
        next if substr($_, -2) eq '::';

        # Only check for methods, since that's all that's reliable
        if (defined *{"${class}::$_"}{CODE}) {
            if ($_ eq 'AUTOLOAD' and \&{"${class}::$_"} == \&_AUTOLOAD) {
                # This is a Class::Autouse hook.  Ignore.
                next;
            }
            return 1;
        }
    }

    '';
}

# For a given class, get the file name
sub _class_file ($) {
    join( '/', split /(?:\'|::)/, shift ) . '.pm';
}

# Establish our call depth
sub _depth {
    my $spaces = shift;
    if ( DEBUG and ! $spaces ) {
        _debug(\@_);
    }

    # Search up the caller stack to find the first call that isn't us.
    my $level = 0;
    while( $level++ < 1000 ) {
        my @call = caller($level);
        if ( @call ) {
            next if $call[3] eq '(eval)';
            next if $call[3] =~ /^Class::Autouse::\w+\z/;
        }

        # Subtract 1 for this sub's call
        $level -= 1;
        return $spaces ? join( '', (' ') x ($level - 2)) : $level;
    }

    Carp::croak('Infinite loop trying to find call depth');
}

# Die gracefully
sub _cry {
    _debug() if DEBUG;
    local $Carp::CarpLevel = $Carp::CarpLevel;
    $Carp::CarpLevel += _depth();
    $_[0] =~ s/\s+at\s+\S+Autouse\.pm line \d+\.$//;
    Carp::croak($_[0]);
}

# Adaptive debug print generation
BEGIN {
    eval <<'END_DEBUG' if DEBUG;

sub _debug {
	my $args    = shift;
	my $method  = !! shift;
	my $message = shift || '';
	my @c       = caller(1);
	my $msg     = _depth(1) . $c[3];
	if ( ref $args ) {
		my @mapped = map { defined $_ ? "'$_'" : 'undef' } @$args;
		shift @mapped if $method;
		$msg .= @mapped ? '( ' . ( join ', ', @mapped ) . ' )' : '()';
	}
	print "$msg$message\n";
}

END_DEBUG
}

#####################################################################
# Final Initialisation

# The _UPDATE_HOOKS function is intended to turn our hijacking of UNIVERSAL::can
# on or off, depending on whether we have any live hooks. The idea being, if we
# don't have any live hooks, why bother intercepting UNIVERSAL::can calls?
sub _UPDATE_HOOKS () {
    local $^W = 0;
    *UNIVERSAL::can = $HOOKS ? \&_can : $ORIGINAL_CAN;
    *UNIVERSAL::isa = $HOOKS ? \&_isa : $ORIGINAL_ISA;
}

# The _GLOBAL_HOOKS function turns on the universal autoloader hooks
sub _GLOBAL_HOOKS () {
    return if \&UNIVERSAL::AUTOLOAD == \&_UNIVERSAL_AUTOLOAD;

    # Overwrite UNIVERSAL::AUTOLOAD and catch any
    # UNIVERSAL::DESTROY calls so they don't trigger
    # UNIVERSAL::AUTOLOAD. Anyone handling DESTROY calls
    # via an AUTOLOAD should be summarily shot.
    *UNIVERSAL::AUTOLOAD = \&_UNIVERSAL_AUTOLOAD;
    *UNIVERSAL::DESTROY  = \&_UNIVERSAL_DESTROY;

    # Because this will never go away, we increment $HOOKS such
    # that it will never be decremented, and thus the
    # UNIVERSAL::can/isa hijack will never be removed.
    _UPDATE_HOOKS() unless $HOOKS++;
}

BEGIN {
    # Optional integration with prefork.pm (if installed)
    local $@;
    eval { require prefork };
    if ( $@ ) {
        # prefork is not installed.
        # Do manual detection of mod_perl
        $DEVEL = 1 if $ENV{MOD_PERL};
    } else {
        # Go into devel mode when prefork is enabled
        $LOADED{prefork} = 1;
        local $@;
        eval "prefork::notify( sub { Class::Autouse->devel(1) } )";
        die $@ if $@;
    }
}

1;

__END__

=pod

=head1 NAME

Class::Autouse - Run-time load a class the first time you call a method in it.

=head1 SYNOPSIS

    ##################################################################
    # SAFE FEATURES

    # Debugging (if you go that way) must be set before the first use
    BEGIN {
        $Class::Autouse::DEBUG = 1;
    }

    # Turn on developer mode (always load immediately)
    use Class::Autouse qw{:devel};

    # Load a class on method call
    use Class::Autouse;
    Class::Autouse->autouse( 'CGI' );
    print CGI->b('Wow!');

    # Use as a pragma
    use Class::Autouse qw{CGI};

    # Use a whole module tree
    Class::Autouse->autouse_recursive('Acme');

    # Disable module-existance check, and thus one additional 'stat'
    # per module, at autouse-time if loading modules off a remote
    # network drive such as NFS or SMB.
    # (See below for other performance optimizations.)
    use Class::Autouse qw{:nostat};

    ##################################################################
    # UNSAFE FEATURES

    # Turn on the Super Loader (load all classes on demand)
    use Class::Autouse qw{:superloader};

    # Autouse classes matching a given regular expression
    use Class::Autouse qr/::Test$/;

    # Install a class generator (instead of overriding UNIVERSAL::AUTOLOAD)
    # (See below for a detailed example)
    use Class::Autouse \&my_class_generator;

    # Add a manual callback to UNIVERSAL::AUTOLOAD for syntactic sugar
    Class::Autouse->sugar(\&my_magic);

=head1 DESCRIPTION

B<Class::Autouse> is a runtime class loader that allows you to specify
classes that will only load when a method of that class is called.

For large classes or class trees that might not be used during the running
of a program, such as L<Date::Manip>, this can save you large amounts of
memory, and decrease the script load time a great deal.

B<Class::Autouse> also provides a number of "unsafe" features for runtime
generation of classes and implementation of syntactic sugar. These features
make use of (evil) UNIVERSAL::AUTOLOAD hooking, and are implemented in
this class because these hooks can only be done by a one module, and
Class::Autouse serves as a useful place to centralise this kind of evil :)

=head2 Class, not Module

The terminology "class loading" instead of "module loading" is used
intentionally. Modules will only be loaded if they are acting as a class.

That is, they will only be loaded during a Class-E<gt>method call. If you try
to use a subroutine directly, say with C<Class::method()>, the class will
not be loaded and a fatal error will mostly likely occur.

This limitation is made to allow more powerfull features in other areas,
because we can focus on just loading the modules, and not have
to deal with importing.

And really, if you are doing OO Perl, you should be avoiding importing
wherever possible.

=head2 Use as a pragma

Class::Autouse can be used as a pragma, specifying a list of classes
to load as the arguments. For example

   use Class::Autouse qw{CGI Data::Manip This::That};

is equivalent to

   use Class::Autouse;
   Class::Autouse->autouse( 'CGI'         );
   Class::Autouse->autouse( 'Data::Manip' );
   Class::Autouse->autouse( 'This::That'  );

=head2 Developer Mode

C<Class::Autouse> features a developer mode. In developer mode, classes
are loaded immediately, just like they would be with a normal 'use'
statement (although the import sub isn't called).

This allows error checking to be done while developing, at the expense of
a larger memory overhead. Developer mode is turned on either with the
C<devel> method, or using :devel in any of the pragma arguments.
For example, this would load CGI.pm immediately

    use Class::Autouse qw{:devel CGI};

While developer mode is roughly equivalent to just using a normal use
command, for a large number of modules it lets you use autoloading
notation, and just comment or uncomment a single line to turn developer
mode on or off. You can leave it on during development, and turn it
off for speed reasons when deploying.

=head2 Recursive Loading

As an alternative to the super loader, the C<autouse_recursive> and
C<load_recursive> methods can be used to autouse or load an entire tree
of classes.

For example, the following would give you access to all the L<URI>
related classes installed on the machine.

    Class::Autouse->autouse_recursive( 'URI' );

Please note that the loadings will only occur down a single branch of the
include path, whichever the top class is located in.

=head2 No-Stat Mode

For situations where a module exists on a remote disk or another relatively
expensive location, you can call C<Class::Autouse> with the :nostat param
to disable initial file existance checking at hook time.

  # Disable autoload-time file existance checking
  use Class::Autouse qw{:nostat};

=head2 Super Loader Mode

Turning on the C<Class::Autouse> super loader allows you to automatically
load B<ANY> class without specifying it first. Thus, the following will
work and is completely legal.

    use Class::Autouse qw{:superloader};

    print CGI->b('Wow!');

The super loader can be turned on with either the
C<Class::Autouse-E<gt>>superloader> method, or the C<:superloader> pragma
argument.

Please note that unlike the normal one-at-a-time autoloading, the
super-loader makes global changes, and so is not completely self-contained.

It has the potential to cause unintended effects at a distance. If you
encounter unusual behaviour, revert to autousing one-at-a-time, or use
the recursive loading.

Use of the Super Loader is highly discouraged for widely distributed
public applications or modules unless unavoidable. B<Do not use> just
to be lazy and save a few lines of code.

=head2 Loading with Regular Expressions

As another alternative to the superloader and recursive loading, a compiled
regular expression (qr//) can be supplied as a loader.  Note that this
loader implements UNIVERSAL::AUTOLOAD, and has the same side effects as the
superloader.

=head2 Registering a Callback for Dynamic Class Creation

If none of the above are sufficient, a CODE reference can be given
to Class::Autouse.  Any attempt to call a method on a missing class
will launch each registered callback until one returns true.

Since overriding UNIVERSAL::AUTOLOAD can be done only once in a given
Perl application, this feature allows UNIVERSAL::AUTOLOAD to be shared.
Please use this instead of implementing your own UNIVERSAL::AUTOLOAD.

See the warnings under the L<Super Loader Module> above which
apply to all of the features which override UNIVERSAL::AUTOLOAD.

It is up to the callback to define the class, the details of which
are beyond the scope of this document.   See the example below for
a quick reference:

=head3 Callback Example

Any use of a class like Foo::Wrapper autogenerates that class as a proxy
around Foo.

    use Class::Autouse sub {
        my ($class) = @_;
        if ($class =~ /(^.*)::Wrapper/) {
            my $wrapped_class = $1;
            eval "package $class; use Class::AutoloadCAN;";
            die $@ if $@;
            no strict 'refs';
            *{$class . '::new' } = sub {
                my $class = shift;
                my $proxy = $wrapped_class->new(@_);
                my $self = bless({proxy => $proxy},$class);
                return $self;
            };
            *{$class . '::CAN' } = sub {
                my ($obj,$method) = @_;
                my $delegate = $wrapped_class->can($method);
                return unless $delegate;
                my $delegator = sub {
                    my $self = shift;
                    if (ref($self)) {
                        return $self->{proxy}->$method(@_);
                    }
                    else {
                        return $wrapped_class->$method(@_);
                    }
                };
                return *{ $class . '::' . $method } = $delegator;
            };

            return 1;
        }
        return;
    };

    package Foo;
    sub new { my $class = shift; bless({@_},$class); }
    sub class_method { 123 }
    sub instance_method {
        my ($self,$v) = @_;
        return $v * $self->some_property
    }
    sub some_property { shift->{some_property} }


    package main;
    my $x = Foo::Wrapper->new(
        some_property => 111,
    );
    print $x->some_property,"\n";
    print $x->instance_method(5),"\n";
    print Foo::Wrapper->class_method,"\n";

=head2 sugar

This method is provided to support "syntactic sugar": allowing the developer
to put things into Perl which do not look like regular Perl.  There are
several ways to do this in Perl.  Strategies which require overriding
UNIVERSAL::AUTOLOAD can use this interface instead to share that method
with the superloader, and with class gnerators.

When Perl is unable to find a subroutine/method, and all of the class loaders
are exhausted, callbacks registered via sugar() are called.  The callbacks
recieve the class name, method name, and parameters of the call.

If the callback returns nothing, Class::Autouse will continue to iterate through
other callbacks.  The first callback which returns a true value will
end iteration.  That value is expected to be a CODE reference which will respond
to the AUTOLOAD call.

Note: The sugar callback(s) will only be fired by UNIVERSAL::AUTOLOAD after all
other attempts at loading the class are done, and after attempts to use regular
AUTOLOAD to handle the method call.  It is never fired by isa() or can().  It
will fire repatedly for the same class.  To generate classes, use the
regular CODE ref support in autouse().

=head3 Syntactic Sugar Example

    use Class::Autouse;
    Class::Autouse->sugar(
        sub {
            my $caller = caller(1);
            my ($class,$method,@params) = @_;
            shift @params;
            my @words = ($method,$class,@params);
            my $sentence = join(" ",@words);
            return sub { $sentence };
        }
    );

    $x = trolls have big ugly hairy feet;

    print $x,"\n";
    # trolls have big ugly hairy feet

=head2 mod_perl

The mechanism that C<Class::Autouse> uses is not compatible with L<mod_perl>.
In particular with reloader modules like L<Apache::Reload>. C<Class::Autouse>
detects the presence of mod_perl and acts as normal, but will always load
all classes immediately, equivalent to having developer mode enabled.

This is actually beneficial, as under mod_perl classes should be preloaded
in the parent mod_perl process anyway, to prevent them having to be loaded
by the Apache child classes. It also saves HUGE amounts of memory.

Note that dynamically generated classes and classes loaded via regex CANNOT
be pre-loaded automatically before forking child processes.  They will still
be loaded on demand, often in the child process.  See L<prefork> below.

=head2 prefork

As with mod_perl, C<Class::Autouse> is compatible with the L<prefork> module,
and all modules specifically autoloaded will be loaded before forking correctly,
when requested by L<prefork>.

Since modules generated via callback or regex cannot be loaded automatically
by prefork in a generic way, it's advised to use prefork directly to load/generate
classes when using mod_perl.

=head2 Performance Optimizatons

=over

=item :nostat

Described above, this option is useful when the module in question is on
remote disk.

=item :noprebless

When set, Class::Autouse presumes that objects which are already blessed
have their class loaded.

This is true in most cases, but will break if the developer intends to
reconstitute serialized objects from Data::Dumper, FreezeThaw or its
cousins, and has configured Class::Autouse to load the involved classes
just-in-time.

=item :staticisa

When set, presumes that @ISA will not change for a class once it is loaded.
The greatest grandparent of a class will be given back the original can/isa
implementations which are faster than those Class::Autouse installs into
UNIVERSAL.  This is a performance tweak useful in most cases, but is left
off by default to prevent obscure bugs.

=back

=head2 The Internal Debugger

Class::Autouse provides an internal debugger, which can be used to debug
any weird edge cases you might encounter when using it.

If the C<$Class::Autouse::DEBUG> variable is true when C<Class::Autouse>
is first loaded, debugging will be compiled in. This debugging prints
output like the following to STDOUT.

    Class::Autouse::autouse_recursive( 'Foo' )
        Class::Autouse::_recursive( 'Foo', 'load' )
            Class::Autouse::load( 'Foo' )
            Class::Autouse::_children( 'Foo' )
            Class::Autouse::load( 'Foo::Bar' )
                Class::Autouse::_file_exists( 'Foo/Bar.pm' )
                Class::Autouse::load -> Loading in Foo/Bar.pm
            Class::Autouse::load( 'Foo::More' )
                etc...

Please note that because this is optimised out if not used, you can
no longer (since 1.20) enable debugging at run-time. This decision was
made to remove a large number of unneeded branching and speed up loading.

=head1 METHODS

=head2 autouse $class, ...

The autouse method sets one or more classes to be loaded as required.

=head2 load $class

The load method loads one or more classes into memory. This is functionally
equivalent to using require to load the class list in, except that load
will detect and remove the autoloading hook from a previously autoused
class, whereas as use effectively ignore the class, and not load it.

=head2 devel

The devel method sets development mode on (argument of 1) or off
(argument of 0).

If any classes have previously been autouse'd and not loaded when this
method is called, they will be loaded immediately.

=head2 superloader

The superloader method turns on the super loader.

Please note that once you have turned the superloader on, it cannot be
turned off. This is due to code that might be relying on it being there not
being able to autoload its classes when another piece of code decides
they don't want it any more, and turns the superloader off.

=head2 class_exists $class

Handy method when doing the sort of jobs that C<Class::Autouse> does. Given
a class name, it will return true if the class can be loaded ( i.e. in @INC ),
false if the class can't be loaded, and undef if the class name is invalid.

Note that this does not actually load the class, just tests to see if it can
be loaded. Loading can still fail. For a more comprehensive set of methods
of this nature, see L<Class::Inspector>.

=head2 autouse_recursive $class

The same as the C<autouse> method, but autouses recursively.

=head2 load_recursive $class

The same as the C<load> method, but loads recursively. Great for checking that
a large class tree that might not always be loaded will load correctly.

=head1 SUPPORT

Bugs should be always be reported via the CPAN bug tracker at

L<http://rt.cpan.org/NoAuth/ReportBug.html?Queue=Class-Autouse>

For other issues, or commercial enhancement or support, contact the author.

=head1 AUTHORS

Adam Kennedy E<lt>cpan@ali.asE<gt>

Scott Smith E<lt>sakoht@cpan.orgE<gt>

Rob Napier E<lt>rnapier@employees.orgE<gt>

=head1 SEE ALSO

L<autoload>, L<autoclass>

=head1 COPYRIGHT

Copyright 2002 - 2012 Adam Kennedy.

This program is free software; you can redistribute
it and/or modify it under the same terms as Perl itself.

The full text of the license can be found in the
LICENSE file included with this module.

=cut
