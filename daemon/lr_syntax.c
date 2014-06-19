#include "lr_syntax.h"

int lrSyntax(int fd, char *buffer)
{
	char *ptr;

	ptr = strstr(buffer, message(MSG_EQ_CMD));

	if (ptr != buffer) {
		return 1;
	} else {
		char *lr_ans = calloc(MAX_MSG_SIZE, sizeof(char));

		if (fork() == 0) {
			close(fd);

			#if !defined(__OpenBSD__) && defined(__FreeBSD__)

			execl("/var/www/imscp/engine/imscp-rqst-mngr", "imscp-rqst-mngr", (char*)NULL);

			#else

			execl("/usr/local/www/imscp/engine/imscp-rqst-mngr", "imscp-rqst-mngr", (char*)NULL);

			#endif

			exit(0);
		}

		strcat(lr_ans, message(MSG_CMD_OK));
		strcat(lr_ans, "request is being processed.\n");

		if (sendLine(fd, lr_ans, strlen(lr_ans)) < 0) {
			free(lr_ans);

			return -1;
		}
	}

	return 0;
}
