#ifndef _TAKE_CONNECTION_H

#define _TAKE_CONNECTION_H

#include "defs.h"

#include <unistd.h>

/* str*() stuff ;) */

#include <string.h>

extern char client_ip [MAX_MSG_SIZE];

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_line(int fd, char *src, size_t len);

extern int helo_cmd(int fd);

extern int lr_cmd(int fd, char *msg);

extern int bye_cmd(int fd, char *msg);

extern int recv_line(int fd, char *dest, size_t n);

void take_connection(int sockfd);

#endif
