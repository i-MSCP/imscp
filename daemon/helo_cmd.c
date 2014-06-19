#include "helo_cmd.h"

int heloCommand(int fd)
{
	char *buffer = calloc(MAX_MSG_SIZE, sizeof(char));
	int res;

	while (1) {
		memset(buffer, '\0', MAX_MSG_SIZE);

		if (receiveLine(fd, buffer, MAX_MSG_SIZE - 1) <= 0) {
			free(buffer);
			return -1;
		}

		res = heloSyntax(fd, buffer);

		if (res == -1) {
			free(buffer);
			return -1;
		} else if (res == 1) {
			continue;
		} else {
			break;
		}
	}

	free(buffer);

	return 0;
}
