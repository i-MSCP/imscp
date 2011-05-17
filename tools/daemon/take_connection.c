#include "take_connection.h"

#include <stdlib.h>

void take_connection(int sockfd) {

	int status;
	char *buff;

	/* chek for client ip */
	if (strcmp(client_ip, "127.0.0.1")) {
		close(sockfd);

		return;
	}

	send_line(sockfd, message(MSG_WELCOME), strlen(message(MSG_WELCOME)));

	if (helo_cmd(sockfd)) {
		close(sockfd);
		return;
	}

	buff = calloc(MAX_MSG_SIZE, sizeof(char));

	while (1) {
		memset(buff, '\0', MAX_MSG_SIZE);

		if (recv_line(sockfd, buff, MAX_MSG_SIZE - 1) <= 0) {
			free(buff);
			break;
		} else {
			status = lr_cmd(sockfd, buff);

			/* if something went wrong break */
			if (status <= -1) {
				break;
			/* if it went ok continue */
			} else if (status == 0) {
				continue;
			/* else: nothing happened, this command wasn't requested */
			} else {
				status = bye_cmd(sockfd, buff);

				if (status <= 0 ||
					send_line(
						sockfd,
						message(MSG_BAD_SYNTAX),
						strlen(message(MSG_BAD_SYNTAX))
					) < 0) {
					break;
				}
			}
		}
	}

	sleep(1);
	close(sockfd);
}
