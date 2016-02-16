#include "daemon_init.h"

void daemonInit(char *pidfile)
{
	pid_t pid;

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
	openlog(message(MSG_DAEMON_NAME), LOG_PID, SYSLOG_FACILITY);

	/* Create pidfile if needed */
	if(pidfile != NULL) {
		FILE *file = fopen(pidfile, "w");
		fprintf(file, "%ld", (long)getpid());
		fclose(file);
	}

	say("%s", message(MSG_DAEMON_STARTED));
}
