#include "daemon_init.h"

void daemonInit(char *pidfile)
{
	/* daemonize */
	if(daemon(1, 1) > 1) {
		exit(errno);
	}

	/* open log */
	openlog(message(MSG_DAEMON_NAME), LOG_PID, SYSLOG_FACILITY);

	/* Create pidfile if needed */
	if(pidfile != NULL) {
		FILE *file = fopen(pidfile, "w");
		fprintf(file, "%ld", (long)getpid());
		fclose(file);
	}

	say("%s", message(MSG_DAEMON_STARTED));
}
