#include "signal-handlers.h"

void sigChild (int signo)
{
	pid_t pid;
	int stat;
	char *nmb = calloc(50, sizeof(char));

	while (( pid = waitpid(-1, &stat, WNOHANG)) > 0) {
		sprintf(nmb, "%d", pid);
		memset((void *) nmb, '\0', 50);
	}

	free(nmb);
	signal(SIGCHLD, sigChild);
}

void sigPipe(int signo)
{
	say("%s", message(MSG_SIG_PIPE));
	signal(SIGPIPE, sigPipe);
}
