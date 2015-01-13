#include "bye_syntax.h"

int byeSyntax(int fd, char *buffer)
{
	char *ptr;
	ptr = strstr(buffer, message(MSG_BYE_CMD));

	if (ptr != buffer) {
		return 1;
	} else {
		char *bye_answer = calloc(MAX_MSG_SIZE, sizeof(char));

		strcat(bye_answer, message(MSG_CMD_OK));
		strcat(bye_answer, message(MSG_GOOD_BYE));

		if (sendLine(fd, bye_answer,  strlen(bye_answer)) < 0) {
			free(bye_answer);
			return -1;
		}

		free(bye_answer);
	}

	return 0;
}
