#include "daemon_cmd.h"

int helo_command(int sockfd, char *buffer, char *cliaddr)
{
    char *ptr = strstr(buffer, message(MSG_HELO_CMD));

    if(ptr != buffer) {
        return -2;
    } else {
        char *answer = (char *) calloc(MAX_MSG_SIZE, sizeof(char));
        strcat(answer, message(MSG_CMD_OK));
        strcat(answer, cliaddr);
        strcat(answer, "\n");

        if (write_line(sockfd, answer, strlen(answer)) < 0) {
            free(answer);
            return -1;
        }

        free(answer);
    }

    return 0;
}

int backend_command(int sockfd, char *buffer)
{
    char *ptr = strstr(buffer, message(MSG_EQ_CMD));

    if (ptr != buffer) {
        return -2;
    }

    switch(fork()) {
        case -1:
            say("couldn't fork(): %s", strerror(errno));
        break;
        case 0: /* child */
            close(sockfd);
            if(execl(backendscriptpath, backendscriptname, (char *)NULL) == -1) {
                say("couldn't execute backend command: %s", strerror(errno));
                exit(EXIT_FAILURE);
            }
            break;
        default: { /* parent */
            char *answer = (char *) calloc(MAX_MSG_SIZE, sizeof(char));
            strcat(answer, message(MSG_CMD_OK));
            strcat(answer, message(MSG_CMD_ANSWER));

            if (write_line(sockfd, answer, strlen(answer)) < 0) {
                free(answer);
                return -1;
            }

            free(answer);
        }
    }

    return 0;
}

int bye_command(int sockfd, char *buffer)
{
    char *ptr = strstr(buffer, message(MSG_BYE_CMD));

    if (ptr != buffer) {
        return -2;
    } else {
        char *answer = (char *) calloc(MAX_MSG_SIZE, sizeof(char));
        strcat(answer, message(MSG_CMD_OK));
        strcat(answer, message(MSG_GOOD_BYE));

        if (write_line(sockfd, answer,  strlen(answer)) < 0) {
            free(answer);
            return -1;
        }

        free(answer);
    }

    return 0;
}
