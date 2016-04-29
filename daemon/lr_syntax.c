#include "lr_syntax.h"

int lrSyntax(int fd, char *buffer)
{
	char *backendscriptbasename;
	char *backendscriptpathdup;
	char *ptr = strstr(buffer, message(MSG_EQ_CMD));
	char *lr_answer;
	char *backendscriptpathPtr = (char *)&backendscriptpath;

	if (ptr != buffer) {
		return 1;
	}

	if (fork() == 0) {
		close(fd);
		backendscriptpathdup = strdup(backendscriptpathPtr);
		backendscriptbasename = basename(backendscriptpathdup);
		execl(backendscriptpathPtr, backendscriptbasename, (char *)NULL);
		free(backendscriptpathdup);
		exit(0);
	}

	lr_answer = calloc(MAX_MSG_SIZE, sizeof(char));
	strcat(lr_answer, message(MSG_CMD_OK));
	strcat(lr_answer, message(MSG_CMD_ANSWER));

	if (sendLine(fd, lr_answer, strlen(lr_answer)) < 0) {
		free(lr_answer);
		return -1;
	}

	free(lr_answer);
	return 0;
}
