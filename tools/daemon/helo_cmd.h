#ifndef _HELO_CMD_H

#define _HELO_CMD_H

#define NO_ERROR                0

#define MAX_MSG_SIZE	        1025

#include <sys/types.h>

#include <stdlib.h>

/* memset() struff; */

#include <string.h>

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

/*
 extern char *message(int message_number);


 extern int send_line(int fd, char *src, size_t len);
 */

extern void say(char *format, char *message);

extern int recv_line(int fd, char *dest, size_t n);

extern int helo_syntax(int fd, license_data_type *ld, char *buff);

int helo_cmd(int fd, license_data_type *ld);

#else
#
#endif
