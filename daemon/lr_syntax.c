#include "lr_syntax.h"

int lrSyntax(int fd, char *buffer)
{
	char *ptr;
	time_t timestamp;

	/* OpenBSD or FreeBSD OLD Routine */
	#if defined(__OpenBSD__) || defined(__FreeBSD__)
	char qcommand[MAX_MSG_SIZE];
	#endif

	ptr = strstr(buffer, message(MSG_EQ_CMD));

	if (ptr != buffer) {
		return (1);
	} else {
		char *lr_ans = calloc(MAX_MSG_SIZE, sizeof(char));

		#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
		char fname1[MAXPATHLEN];
		char fname2[MAXPATHLEN];
		char daemon_path[MAXPATHLEN];
		#endif

		if (fork() == 0) {
			int fdres, dupres;
			char logfile[MAXPATHLEN];

			/* execute it */

			close(fd);
			timestamp = time(NULL);

			/* make command with timestamps */
			#if !defined(__OpenBSD__) && !defined(__FreeBSD__)

			sprintf(fname1, "/proc/%ld/exe", (long int) getpid());
			memset(fname2, 0, sizeof (fname2));

			if (readlink(fname1, fname2, sizeof (fname2)) > 0) {
				strncpy(daemon_path, fname2, strlen(fname2)-strlen("daemon/imscp_daemon"));
				strcat(daemon_path, "engine/imscp-rqst-mngr");
				fdres = open ("/dev/null", O_RDONLY);

				if(fdres == -1) {
					say("Error in reopening stdin: %s", strerror(errno));
					exit(128);
				}

				dupres = dup2(fdres, 0); /* reassign 0 */

				if( dupres == -1) {
					say("Error in reassigning stdin: %s", strerror(errno));
					exit(128);
				} else if(dupres != fdres) {
					close (fdres);
				}

				memset(logfile, 0, sizeof (logfile));
				sprintf(logfile, "%s.%ld", LOG_DIR"/"STDOUT_LOG, (long int) timestamp);
				fdres = creat( logfile, S_IRUSR | S_IWUSR );

				if(fdres == -1) {
					say("Error in opening stdout: %s", strerror(errno));
					exit(128);
				}

				dupres = dup2(fdres, 0); /* reassign 0*/

				if( dupres == -1) {
					say("Error in reassigning stdout: %s", strerror(errno));
					exit(128);
				} else if( dupres != fdres) {
					close (fdres);
				}

				memset(logfile, 0, sizeof (logfile));

				sprintf(logfile, "%s.%ld", LOG_DIR"/"STDERR_LOG, (long int) timestamp);
				fdres = creat(logfile,  S_IRUSR | S_IWUSR);

				if(fdres == -1) {
					say("Error in opening stderr: %s", strerror(errno));
					exit(128);
				}

				dupres = dup2(fdres, 0); /* reassign 0*/

				if( dupres == -1) {
					say("Error in reassigning stderr: %s", strerror(errno));
					exit(128);
				} else if( dupres != fdres) {
					close (fdres);
				}

				execl(daemon_path, "imscp-rqst-mngr" ,(char*)NULL);
			}

			#else

			/* OpenBSD or FreeBSD OLD Routine - Temporary HARDCODED */
			memset((void *) &qcommand, '\0', (size_t) sizeof(MAX_MSG_SIZE));

			sprintf(
				qcommand,
				"%s 1>%s/%s.%ld 2>%s/%s.%ld",
				"/usr/local/www/imscp/engine/imscp-rqst-mngr",
				LOG_DIR,
				STDOUT_LOG,
				(long int) timestamp,
				LOG_DIR,
				STDERR_LOG,
				(long int) timestamp
			);

			system(qcommand);

			#endif

			exit(0);
		}

		strcat(lr_ans, message(MSG_CMD_OK));
		strcat(lr_ans, "request is being processed.\n");

		if (sendLine(fd, lr_ans, strlen(lr_ans)) < 0) {
			free(lr_ans);

			return (-1);
		}
	}

	return (NO_ERROR);
}
