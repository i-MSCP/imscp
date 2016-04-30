#ifndef _IMSCP_DAEMON_H
#define _IMSCP_DAEMON_H

#define _POSIX_C_SOURCE 200809L

#include <stdlib.h>
#include <syslog.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <sys/time.h>
#include <signal.h>
#include <string.h>
#include <errno.h>
#include <unistd.h>
#include <stdio.h>
#include <arpa/inet.h>
#include "defs.h"

struct timeval *tv_rcv;
struct timeval *tv_snd;

char backendscriptpath[256];

int notification_pipe[2];

extern void daemonInit(void);
extern char * message(int message_number);
extern void say(char *format, char *message);
extern void sigChild (int signo);
extern void sigPipe(int signo);
extern void takeConnection(int sockfd);
extern void notify_parent(int status);

#endif
