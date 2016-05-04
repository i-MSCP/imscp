#ifndef _DAEMON_CONN_H
#define _DAEMON_CONN_H

#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <arpa/inet.h>

#include "daemon_globals.h"

void handle_client_connection(int sockfd, struct sockaddr *cliaddr);

extern char *message(int message_number);
extern int read_line(int fd, char *dest, size_t n);
extern int write_line(int fd, char *src, size_t n);
extern int helo_command(int fd, char *buffer, char *cliaddr);
extern int backend_command(int fd, char *msg);
extern int bye_command(int fd, char *msg);

#endif
