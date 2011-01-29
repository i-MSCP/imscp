package Common::DecoratorClass;
use Carp;
use strict;
use warnings;
use vars qw ( $METHOD $AUTOLOAD $Dispatchers );
use iMSCP::Debug;
use iMSCP::Exception;

sub new {
	my ($proto, $args) = @_;
	my $class = ref($proto) || $proto;
	bless {
		pre		=> eval "\$${class}::Dispatchers->{pre}"	|| sub {},
		post	=> eval "\$${class}::Dispatchers->{post}"	|| sub {},
		obj		=> $args->{obj}								|| iMSCP::Exception->new("decorator must be constructed with a component to be decorated"),
	}, $class;
}
sub _get_property{
	my $self 		= shift;
	my $property	= shift;
	return $self->{$property} if(exists $self->{$property});
	if($self->{obj}->isa('Common::DecoratorClass')){
		return $self->{obj}->_get_property($property);
	} else {
		return $self->{obj}->{$property};
	}
}
sub DESTROY {}
sub AUTOLOAD {
	my ($self, @args) = @_;
	if ($AUTOLOAD =~ /.+::(.+)$/) {
		$METHOD = $1;
	} else {
		iMSCP::Exception->new("cannot find method name");
	}
	return if($METHOD =~ /DESTROY|can/);
	my $dispatch = $self->{obj}->can($METHOD);
	my $sub = sub {
		my ($decorator, @args) = @_;
		my ($pre, $post) = ($decorator->{pre}, $decorator->{post});
		if (wantarray) {
			() = $pre->($decorator, @args);
			#my @return_values = $decorator->{obj}->$METHOD(@args);
			#() = $post->($decorator, @args);
			#return @return_values;
		} else {
			$pre->($decorator, @args);
			#my $return_value = $decorator->{obj}->$METHOD(@args);
			#$post->($decorator, @args);
			#return $return_value;
		}
	};
	{
		no strict "refs"; # keep following line happy
		*{$AUTOLOAD} = $sub;
	}
	#if (wantarray) {
		#my @return_values = $sub->($self, @args);
		#return @return_values;
	#} else {
		#my $return_value = $sub->($self, @args);
		#return $return_value;
	#}
}

1;
