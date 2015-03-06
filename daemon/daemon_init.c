#include "daemon_init.h"

void daemonInit(const char *pname, int facility)
{
	pid_t pid;
	int i;

	pid = fork();

	if(pid < 0)
		exit(EXIT_FAILURE);

	if(pid > 0)
		exit(EXIT_SUCCESS);

	if (setsid() < 0)
		exit(EXIT_FAILURE);

	signal(SIGHUP, SIG_IGN);

	pid = fork();

	if (pid < 0)
		exit(EXIT_FAILURE);

	if (pid > 0)
		exit(EXIT_SUCCESS);

	umask(0);

	if(chdir("/") < 0)
		exit(EXIT_FAILURE);

	for (i = sysconf(_SC_OPEN_MAX); i > 0; i--) {
		close (i);
	}

	openlog(pname, LOG_PID, facility);
}
