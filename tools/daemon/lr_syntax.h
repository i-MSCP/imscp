#ifndef _LR_SYNTAX_H

#define _LR_SYNTAX_H

#include "defs.h"

#include <sys/types.h>

#include <stdlib.h>

#include <string.h>

#include <stdio.h>

#include <unistd.h>

/*
 timestamp generation includes
 */

#include <time.h>

#include <sys/time.h>

#define LOG_DIR "/var/log/imscp"

#define STDOUT_LOG "imscp_daemon-stdout.log"

#define STDERR_LOG "imscp_daemon-stderr.log"

extern char *message(int message_number);

extern void say(char *format, char *message);

extern int send_line(int fd, char *src, size_t len);

int lr_syntax(int fd, char *buff);

#endif
