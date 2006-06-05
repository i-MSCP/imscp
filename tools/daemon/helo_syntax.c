
#include "helo_syntax.h"

int helo_syntax(int fd, license_data_type *ld, char *buff)
{
	char *ptr;

	ptr = strstr(buff, message(MSG_HELO_CMD));

	if (ptr != buff || strlen(buff) == 7) {

		if (send_line(fd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX))) < 0) {
			return (-1);
		}

		return (1);

	} else {
        char *helo_ans = calloc(MAX_MSG_SIZE, sizeof(char));
		memset(ld -> ip, '\0', MAX_MSG_SIZE);

		memset(ld -> host, '\0', MAX_MSG_SIZE);

		strcat(ld -> ip, client_ip);

		ptr = strstr(buff, " ");

		strncat(ld -> host, ptr + 1, strlen(ptr + 1) - 2);

        strcat(helo_ans, message(MSG_CMD_OK));
		strcat(helo_ans, ld -> host);
		strcat(helo_ans, "\r\n");


		if (send_line(fd, helo_ans,  strlen(helo_ans)) < 0) {
            free(helo_ans);
			return (-1);
		}

		free(helo_ans);
	}

	return (NO_ERROR);
}
