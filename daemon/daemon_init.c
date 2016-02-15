#include "daemon_init.h"

void daemonInit(const char *pname, int facility)
{
	pid_t pid = 0;

	/* create child process */
	pid = fork();
	if(pid < 0) {
		exit(EXIT_FAILURE);
	}

	/* terminate parent process */
	if(pid > 0) {
		exit(EXIT_SUCCESS);
	}

	/* umask the file mode */
	umask(0);

	/* set new session */
	if(setsid() < 0) {
		exit(EXIT_FAILURE);
	}

	/* change the current wokring directory to root */
	if(chdir("/") < 0) {
		exit(EXIT_FAILURE);
	}

	/* close stdin, stdout and stderr */
	close(STDIN_FILENO);
	close(STDOUT_FILENO);
	close(STDERR_FILENO);

	/* open log */
	openlog(pname, LOG_PID, facility);
}
