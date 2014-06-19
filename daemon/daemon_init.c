#include "daemon_init.h"

int daemonInit(const char *pname, int facility)
{
	pid_t pid;
	int i;

	pid = fork();
	if(pid == -1) {
		return -1;
	} else if(pid != 0) {
		exit(0);
	}

	umask(0);

	if(setsid() == -1) {
		return -1;
	}

	signal(SIGHUP, SIG_IGN);

	if(chdir ("/") == -1) {
		return -1;
	}

	for (i = 0; i < 64; i++) {
		close(i);
	}

	openlog(pname, LOG_PID, facility);

	return 0;
}
