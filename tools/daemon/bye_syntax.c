
#include "bye_syntax.h"

int bye_syntax(int fd, char *buff)
{
	char *ptr;

	ptr = strstr(buff, message(MSG_BYE_CMD));

	if (ptr != buff) {

		if (send_line(fd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX))) < 0) {
			return (-1);
		}

		return (1);

	} else {

		char *bye_ans = calloc(MAX_MSG_SIZE, sizeof(char));

		strcat(bye_ans, message(MSG_CMD_OK));

		strcat(bye_ans, "Good Bye!\r\n");

		if (send_line(fd, bye_ans,  strlen(bye_ans)) < 0) {

			free(bye_ans);

			return (-1);
		}

		free(bye_ans);

	}

	return (NO_ERROR);
}
