#ifndef _BYE_SYNTAX_H

#define _BYE_SYNTAX_H

#include <stdlib.h>

#include <string.h>

#define NO_ERROR                0

#define MAX_MSG_SIZE	        1025

#define MSG_BAD_SYNTAX          10016

#define MSG_CMD_OK              10017

#define MSG_BYE_CMD             10019

extern char *message(int message_number);

extern int send_line(int fd, char *src, size_t len);

int bye_syntax(int fd, char *buff);

#else
#
#endif
