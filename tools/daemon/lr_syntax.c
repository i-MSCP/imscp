
#include "lr_syntax.h"
#include <sys/param.h>

#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
int readlink(char *pathname, char *buf, int bufsize);
#endif

int lr_syntax(int fd, license_data_type *ld, char *buff)
{

	char *ptr;

	char *ptr1;

	char qcommand [MAX_MSG_SIZE];

	time_t tim;

	ptr = strstr(buff, message(MSG_LS_CMD));
	ptr1 = strstr(buff, message(MSG_EQ_CMD));

	if (ptr != buff && ptr1 != buff) {

		if (send_line(fd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX))) < 0) {

			return (-1);

		}

		return (1);

	} else {

		char *lr_ans = calloc(MAX_MSG_SIZE, sizeof(char));

		if (ptr1 == buff) {
			#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
			char fname1[MAXPATHLEN];
			char fname2[MAXPATHLEN];
			char daemon_path[MAXPATHLEN];
			#endif
			/*
			 execute query:
			 chek do we have license status
			 if we have it - execute query and send ok
			 else send ERROR
			 */

			if (fork() == 0 ) {

				/*
				 execute it
				 */

				close(fd);

				tim = time(NULL);

				/*
				 make command with timestamps
				 */
				#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
				sprintf (fname1, "/proc/%ld/exe", (long int) getpid());
				memset (fname2, 0, sizeof (fname2));
				if (readlink (fname1, fname2, sizeof (fname2)) > 0) {
					strncpy(daemon_path, fname2, strlen(fname2)-strlen("daemon/vhcs2_daemon"));
					strcat(daemon_path, "engine/vhcs2-rqst-mngr");
				#endif
					memset((void *) &qcommand, '\0', (size_t) sizeof(MAX_MSG_SIZE));
					#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
					sprintf(qcommand,
							"%s 1>%s/%s.%ld 2>%s/%s.%ld",
							daemon_path,
							LOG_DIR,
							STDOUT_LOG,
							(long int) tim,
							LOG_DIR,
							STDERR_LOG,
							(long int) tim);
					#else
					sprintf(qcommand,
							"%s 1>%s/%s.%ld 2>%s/%s.%ld",
							QUERY_CMD,
							LOG_DIR,
                                                        STDOUT_LOG,
                                                        (long int) tim,
                                                        LOG_DIR,
                                                        STDERR_LOG,
                                                        (long int) tim);
					#endif	
					system(qcommand);
					exit(0);
				#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
				}
				#endif	
			}
			
			strcat(lr_ans, message(MSG_CMD_OK));
			strcat(lr_ans, " query scheduled for execution.\r\n");

			if (send_line(fd, lr_ans, strlen(lr_ans)) < 0) {

				free(lr_ans);

				return (-1);

			}

		}

	} 

	return (NO_ERROR);

}

