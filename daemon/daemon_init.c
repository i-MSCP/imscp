#include "daemon_init.h"

void daemonInit(const char *pname, int facility)
{
	int i;
	pid_t pid;

	if ((pid = fork()) != 0) {
		exit(0);
	}

	setsid();
	signal(SIGHUP, SIG_IGN);

	if ((pid = fork()) != 0) {
		exit(0);
	}

	if(chdir("/") != 0) {
		exit(0);
	}

	umask(0);

	for(i = 0; i < 64; i++) {
		close(i);
	}

	openlog(pname, LOG_PID, facility);
}
