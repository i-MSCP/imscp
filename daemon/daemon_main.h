#ifndef _DAEMON_MAIN_H
#define _DAEMON_MAIN_H

#define _POSIX_C_SOURCE 200809L

#include <errno.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <signal.h>
#include <sys/time.h>
#include <syslog.h>
#include <netinet/in.h>
#include <unistd.h>
#include <libgen.h>

#include "daemon_globals.h"

char *backendscriptpath = NULL;
char *backendscriptname = NULL;

extern int notify_pipe[2];
extern void daemon_init(void);
extern char * message(int message_number);
extern void say(char *format, char *message);
extern void handle_signal(int signo);
extern void handle_client_connection(int sockfd, struct sockaddr *cliaddr);
extern void notify(int status);

#endif
