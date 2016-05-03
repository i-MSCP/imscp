#include "daemon_conn.h"

void handle_client_connection(int sockfd, struct sockaddr *cliaddr)
{
    int retval;
    char *welcome_msg = (char *) calloc(MAX_MSG_SIZE, sizeof(char));

    strcat(welcome_msg, message(MSG_CMD_OK));
    strcat(welcome_msg, message(MSG_WELCOME));
    retval = write_line(sockfd, welcome_msg, strlen(welcome_msg));
    free(welcome_msg);

    if(retval == -1) {
        return;
    }

    {
        struct sockaddr_in *addr_in = (struct sockaddr_in *) cliaddr;
        char *buffer = (char *) malloc(sizeof(char) * MAX_MSG_SIZE);

        /* handle helo command */
        while(1) {
            memset(buffer, '\0', MAX_MSG_SIZE);

            retval = read_line(sockfd, buffer, MAX_MSG_SIZE - 1);
            if(retval == -1) { /* Unexpected error */
                break;
            }

            retval = helo_command(sockfd, buffer, inet_ntoa(addr_in->sin_addr));
            if (retval == -1 /* Unexpected error */
                || retval == 0 /* valid helo command has been received */
            ) {
                break;
            }

            retval = bye_command(sockfd, buffer);
            if(retval == 0) { /* valid bye command has been received */
                retval = -1;
                break;
            }

            if(write_line(sockfd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX))) == -1) {
                break; /* Unexpected error */
            }
        }

        if(retval == -1) {
            free(buffer);
            return;
        }

        /* handle backend command */
        while(1) {
            memset(buffer, '\0', MAX_MSG_SIZE);

            retval = read_line(sockfd, buffer, MAX_MSG_SIZE - 1);
            if(retval == -1) { /* Unexpected error */
                break;
            }

            retval = backend_command(sockfd, buffer);
            if (retval == -1) { /* Unexpected error */
                break;
            }

            if(retval == 0) { /* valid backend command has been received */
                continue;
            }

            retval = bye_command(sockfd, buffer);
            if(retval == 0) { /* valid bye command has been received */
                break;
            }

            if(write_line(sockfd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX))) == -1) {
                break; /* Unexpected error */
            }
        }

        free(buffer);
    }
}
