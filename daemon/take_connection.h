#ifndef _TAKE_CONNECTION_H

#define _TAKE_CONNECTION_H

#include <stdlib.h>
#include <unistd.h>
#include <string.h>

#include "defs.h"

extern char *message(int message_number);

extern int sendLine(int fd, char *src, size_t len);
extern int heloCommand(int fd);
extern int lrCommand(int fd, char *msg);
extern int byeCommand(int fd, char *msg);
extern int receiveLine(int fd, char *dest, size_t n);

void takeConnection(int sockfd);

#endif
