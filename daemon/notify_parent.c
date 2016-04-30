#include "notify_parent.h"

void notify_parent(int status)
{
	int writeval;

	if(status == -1) {
		writeval = 0;
		say("Sending \"0\" (error) to parent via fd=%d", (char *) &notification_pipe[1]);
	} else {
		writeval = 1;
		say("Sending \"1\" (OK) to parent via fd=%d", (char *) &notification_pipe[1]);
	}

	write(notification_pipe[1], &writeval, sizeof(writeval));
	close(notification_pipe[1]);

	if(status == -1) {
		exit(EXIT_FAILURE);
	}
}
