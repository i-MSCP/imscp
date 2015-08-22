package Listener::Test;
use iMSCP::EventManager;
iMSCP::EventManager->getInstance()->register('foo', sub { my $p = shift; $$p = 'OK'; 0 });
1;
__END__
