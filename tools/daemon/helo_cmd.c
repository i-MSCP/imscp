#include "helo_cmd.h"

int helo_cmd(int fd) {

	char *buff = calloc(MAX_MSG_SIZE, sizeof(char));
	int res;

	while (1) {
		memset(buff, '\0', MAX_MSG_SIZE);

		if (recv_line(fd, buff, MAX_MSG_SIZE - 1) <= 0) {
			free(buff);
			return (-1);
		}

		res = helo_syntax(fd, buff);

		if (res == -1) {
			free(buff);
			return (-1);
		} else if (res == 1) {
			continue;
		} else {
			break;
		}
	}

	free(buff);

	return (NO_ERROR);
}
