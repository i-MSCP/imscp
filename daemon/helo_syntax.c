#include "helo_syntax.h"

int heloSyntax(int fd, char *buffer)
{
	char *ptr;
	char *helo_answer = calloc(MAX_MSG_SIZE, sizeof(char));
	ptr = strstr(buffer, message(MSG_HELO_CMD));
	ptr = strstr(buffer, " ");

	strcat(helo_answer, message(MSG_CMD_OK));
	strncat(helo_answer, ptr + 1, strlen(ptr + 1) - 2);
	strcat(helo_answer, "\n");

	if (sendLine(fd, helo_answer, strlen(helo_answer)) < 0) {
		free(helo_answer);
		return -1;
	}

	free(helo_answer);
	return 0;
}
