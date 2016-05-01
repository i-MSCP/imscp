#ifndef _DAEMON_MAIN_H
#define _DAEMON_MAIN_H

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
#include "daemon_globals.h"

struct timeval *tv_rcv;
struct timeval *tv_snd;
char *backendscriptpath = NULL;

extern int notify_pipe[2];
extern void daemon_init(void);
extern char * message(int message_number);
extern void say(char *format, char *message);
extern void handle_signal(int signo);
extern void take_connection(int sockfd);
extern void notify(int status);

#endif
