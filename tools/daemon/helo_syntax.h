#ifndef _HELO_SYNTAX_H

#define _HELO_SYNTAX_H

#define NO_ERROR                0

#define MAX_MSG_SIZE	        1025

#define MSG_HELO_CMD            10015

#define MSG_BAD_SYNTAX          10016

#define MSG_CMD_OK              10017

#include <sys/types.h>

#include <stdlib.h>

#include <string.h>

#include <stdio.h>

typedef struct {

    char ip[MAX_MSG_SIZE];

    char host[MAX_MSG_SIZE];

    /*
     Request data.
     */

    char rd[MAX_MSG_SIZE];

    /*
     Status data.
     */

    char sd[MAX_MSG_SIZE];

} license_data_type;

extern char client_ip[MAX_MSG_SIZE];

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_line(int fd, char *src, size_t len);

int helo_syntax(int fd, license_data_type *ld, char *buff);

#else
#
#endif
