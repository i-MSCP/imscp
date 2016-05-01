#include "daemon_cmd.h"

int helo_command(int fd)
{
    int rs;
    char *buffer = (char *) malloc(sizeof(char) * MAX_MSG_SIZE);

    while (1) {
        memset(buffer, '\0', MAX_MSG_SIZE);

        if (receive_line(fd, buffer, MAX_MSG_SIZE - 1) <= 0) {
            free(buffer);
            return -1;
        }

        rs = helo_syntax(fd, buffer);

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

int helo_syntax(int fd, char *buffer)
{
    char *ptr = strstr(buffer, message(MSG_HELO_CMD));
    ptr = strstr(buffer, " ");

    if(ptr == NULL) {
        send_line(fd, message(MSG_BAD_SYNTAX), strlen(message(MSG_BAD_SYNTAX)));
        return -1;
    } else {
        char *helo_answer = (char *) calloc(MAX_MSG_SIZE, sizeof(char));

        strcat(helo_answer, message(MSG_CMD_OK));
        strncat(helo_answer, ptr + 1, strlen(ptr + 1) - 2);
        strcat(helo_answer, "\n");

        if (send_line(fd, helo_answer, strlen(helo_answer)) < 0) {
            free(helo_answer);
            return -1;
        }

        free(helo_answer);
    }

    return 0;
}

int bye_command(int fd, char *msg)
{
    char *ptr = strstr(msg, message(MSG_BYE_CMD));
    char *bye_answer;

    if (ptr != msg) {
        return 1;
    }

    bye_answer = (char *) calloc(MAX_MSG_SIZE, sizeof(char));
    strcat(bye_answer, message(MSG_CMD_OK));
    strcat(bye_answer, message(MSG_GOOD_BYE));

    if (send_line(fd, bye_answer,  strlen(bye_answer)) < 0) {
        free(bye_answer);
        return -1;
    }

    free(bye_answer);
    return 0;
}

int backend_command(int fd, char *msg)
{
    char *ptr = strstr(msg, message(MSG_EQ_CMD));
    char *lr_answer;

    if (ptr != msg) {
        return 1;
    }

    switch(fork()) {
        case -1:
            say("could not fork(): %s", strerror(errno));
        break;
        case 0: { /* child */
            char *backendscriptpathdup = strdup(backendscriptpath);
            char *backendscriptbasename = basename(backendscriptpathdup);

            close(fd);
            free(backendscriptpathdup);

            if(execl(backendscriptpath, backendscriptbasename, (char *)NULL) == -1) {
                say("could not execute backend command: %s", strerror(errno));
                exit(EXIT_FAILURE);
            }
        }
    }

    lr_answer = (char *) calloc(MAX_MSG_SIZE, sizeof(char));
    strcat(lr_answer, message(MSG_CMD_OK));
    strcat(lr_answer, message(MSG_CMD_ANSWER));

    if (send_line(fd, lr_answer, strlen(lr_answer)) < 0) {
        free(lr_answer);
        return -1;
    }

    free(lr_answer);
    return 0;
}
