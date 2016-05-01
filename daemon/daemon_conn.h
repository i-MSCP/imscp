#ifndef _DAEMON_CONN_H
#define _DAEMON_CONN_H

#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include "daemon_globals.h"

void take_connection(int sockfd);

extern char *message(int message_number);
extern int send_line(int fd, char *src, size_t len);
extern int helo_command(int fd);
extern int backend_command(int fd, char *msg);
extern int bye_command(int fd, char *msg);
extern int receive_line(int fd, char *dest, size_t n);

#endif
