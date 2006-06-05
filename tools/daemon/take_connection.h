#ifndef _TAKE_CONNECTION_H

#define _TAKE_CONNECTION_H

#include <unistd.h>

/* str*() stuff ;) */

#include <string.h>

#define MSG_WELCOME             10001

#define MAX_MSG_SIZE	        1025

extern char client_ip [MAX_MSG_SIZE];

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

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_line(int fd, char *src, size_t len);

extern int helo_cmd(int fd, license_data_type *ld);

extern int lr_cmd(int fd, license_data_type *ld);

extern int bye_cmd(int fd);

void take_connection(int sockfd);

#else
#
#endif
