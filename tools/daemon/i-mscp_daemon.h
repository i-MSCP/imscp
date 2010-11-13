#ifndef _IMSCP_DAEMON_H

#define _IMSCP_DAEMON_H

/* Needed headers. */

/*
 calloc, malloc, free, realloc - Allocate and free dynamic memory
 */

#include <stdlib.h>

/*
 Will we log it ?
 */

#include <syslog.h>

/*
 Socket manipulation functions.
 */

#include <sys/stat.h>
#include <sys/types.h>
#include <sys/socket.h>

/*
 Internet Socket Address Structure IPv4.
 */

#include <netinet/in.h>

/*
 Socket timeouts.
 */

#include <sys/time.h>

/*
 Signal handling.
 */

#include <signal.h>

/*
 String manipulation.
 */

#include <string.h>

/*
 Error handling.
 */

#include <errno.h>

/*
 Something more about fork.
 */

#include <unistd.h>

/*
 String manipulation.
 */

#include <stdio.h>

/*
 inet_ntop() function.
 */

#include <arpa/inet.h>

/*
 Config
 */
#include "defs.h"

/*
 Predefined names.
 */

char client_ip [MAX_MSG_SIZE];

struct timeval     *tv_rcv;

struct timeval     *tv_snd;

/* External functions. */

extern void daemon_init(const char *pname, int facility);

extern char *message(int message_number);

extern void say(char *format, char *message);

extern void sig_child (int signo);

extern void sig_pipe(int signo);

extern void take_connection(int sockfd);

#endif
