#include "lr_syntax.h"

int lrSyntax(int fd, char *buffer)
{
	char *ptr;

	ptr = strstr(buffer, message(MSG_EQ_CMD));

	if (ptr != buffer) {
		return 1;
	} else {
		char *lr_answer = calloc(MAX_MSG_SIZE, sizeof(char));

		if(fork() == 0) {
			close(fd);

			#if !defined(__OpenBSD__) && !defined(__FreeBSD__)
			system("perl /var/www/imscp/engine/imscp-rqst-mngr");
			#else
			system("perl /usr/local/www/imscp/engine/imscp-rqst-mngr");
			#endif

			exit(0);
		}

		strcat(lr_answer, message(MSG_CMD_OK));
		strcat(lr_answer, message(MSG_CMD_ANSWER));

		if (sendLine(fd, lr_answer, strlen(lr_answer)) < 0) {
			free(lr_answer);

			return -1;
		}

		free(lr_answer);
	}

	return 0;
}
