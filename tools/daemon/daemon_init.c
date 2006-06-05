
#include "daemon_init.h"

void daemon_init(const char *pname, int facility)
{

	int i;
	pid_t pid;

	if ((pid = fork()) != 0)
		exit(0);

	setsid();

	signal(SIGHUP, SIG_IGN);

	if ((pid = fork()) != 0)
		exit(0);

	chdir("/");

	umask(0);

	for(i = 0; i < 64; i++)
	close(i);

	openlog(pname, LOG_PID, facility);

}
