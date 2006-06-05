#ifndef _SEND_DATA_H

#define _SEND_DATA_H

#include <sys/types.h>

#include <unistd.h>

#include <errno.h>

#define MSG_ERROR_EINTR         10007

extern char *message(int message_number);

extern void say(char *format, char *message);

int send_data(int fd, char *src, size_t n);

#else
#
#endif
