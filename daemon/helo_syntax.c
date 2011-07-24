#include "helo_syntax.h"

int helo_syntax(int fd, char *buff) {

	char *ptr;
	char *helo_ans = calloc(MAX_MSG_SIZE, sizeof(char));
	ptr = strstr(buff, message(MSG_HELO_CMD));

	ptr = strstr(buff, " ");

	strcat(helo_ans, message(MSG_CMD_OK));
	strcat(helo_ans, client_ip);
	strcat(helo_ans, "/");
	strncat(helo_ans, ptr + 1, strlen(ptr + 1) - 2);
	strcat(helo_ans, "\r\n");

	if (send_line(fd, helo_ans, strlen(helo_ans)) < 0) {
		free(helo_ans);

		return (-1);
	}

	free(helo_ans);

	return (NO_ERROR);
}
