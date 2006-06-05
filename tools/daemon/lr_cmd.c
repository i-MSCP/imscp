
#include "lr_cmd.h"

int lr_cmd(int fd, license_data_type *ld)
{
	char *buff = calloc(MAX_MSG_SIZE, sizeof(char));

	int res;

	for ( ; ; ) {
		memset(buff, '\0', MAX_MSG_SIZE);

		if (recv_line(fd, buff, MAX_MSG_SIZE - 1) <= 0) {
			free(buff);
			return (-1);
		}

		res = lr_syntax(fd, ld, buff);

		if (res == -1) {
			free(buff);
			return (-1);
		} else if (res == 1)
			continue;
		else
			break;
	}

	free(buff);

    return (NO_ERROR);
}

