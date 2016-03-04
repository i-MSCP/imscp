#include "helo_cmd.h"

int heloCommand(int fd)
{
	char *buffer = calloc(MAX_MSG_SIZE, sizeof(char));
	int rs;

	while (1) {
		memset(buffer, '\0', MAX_MSG_SIZE);

		if (receiveLine(fd, buffer, MAX_MSG_SIZE - 1) <= 0) {
			free(buffer);
			return -1;
		}

		rs = heloSyntax(fd, buffer);

		if (rs == -1) {
			free(buffer);
			return -1;
		}

		if (rs == 1) {
			continue;
		}

		break;
	}

	free(buffer);
	return 0;
}
