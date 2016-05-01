#include "daemon_conn.h"

void take_connection(int sockfd)
{
    char *welcome_msg = (char *) calloc(MAX_MSG_SIZE, sizeof(char));

    strcat(welcome_msg, message(MSG_CMD_OK));
    strcat(welcome_msg, message(MSG_WELCOME));

    if(send_line(sockfd, welcome_msg, strlen(welcome_msg)) == 0 && helo_command(sockfd) == 0) {
        int status;
        char *buffer = (char *) malloc(sizeof(char) * MAX_MSG_SIZE);

        while (1) {
            memset(buffer, '\0', MAX_MSG_SIZE);

            if (receive_line(sockfd, buffer, MAX_MSG_SIZE - 1) <= 0) {
                free(buffer);
                break;
            }

            status = backend_command(sockfd, buffer);

            /* if something went wrong break */
            if (status <= -1) {
                break;

            }

            /* if it went ok continue */
            if (status == 0) {
                continue;
            }

            /* nothing happened, this command wasn't requested */
            status = bye_command(sockfd, buffer);

            if (status <= 0 || send_line(sockfd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX))) < 0) {
                break;
            }
        }
    }

    free(welcome_msg);
    close(sockfd);
}
