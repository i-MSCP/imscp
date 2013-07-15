#include "take_connection.h"

void takeConnection(int sockfd)
{
	int status;
	char *buffer;

	/* chek for client ip */
	/*
	if (strcmp(client_ip, "127.0.0.1")) {
		close(sockfd);
		return;
	}
	*/

	sendLine(sockfd, message(MSG_WELCOME), strlen(message(MSG_WELCOME)));

	if (heloCommand(sockfd)) {
		close(sockfd);
		return;
	}

	buffer = calloc(MAX_MSG_SIZE, sizeof(char));

	while (1) {
		memset(buffer, '\0', MAX_MSG_SIZE);

		if (receiveLine(sockfd, buffer, MAX_MSG_SIZE - 1) <= 0) {
			free(buffer);
			break;
		} else {
			status = lrCommand(sockfd, buffer);

			/* if something went wrong break */
			if (status <= -1) {
				break;
			/* if it went ok continue */
			} else if (status == 0) {
				continue;
			/* else: nothing happened, this command wasn't requested */
			} else {
				status = byeCommand(sockfd, buffer);

				if (status <= 0 || sendLine(sockfd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX))) < 0) {
					break;
				}
			}
		}
	}

	sleep(1);
	close(sockfd);
}
