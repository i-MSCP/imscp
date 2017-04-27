#include "daemon_main.h"

int main(int argc, char **argv)
{
    /* parent process */

    int option;
    char *backendscriptpathdup;
    char *pidfile = NULL;

    /* parse command line options */
    while ((option = getopt(argc, argv, "hb:p:")) != -1) {
        switch(option) {
            case 'b':
                backendscriptpathdup = strdup(optarg);
                backendscriptpath = strdup(backendscriptpathdup);
                backendscriptname = basename(backendscriptpathdup);
                free(backendscriptpathdup);
            break;
            case 'p':
                pidfile = strdup(optarg);
            break;
            case 'h':
            default:
                fprintf(stderr, "i-MSCP Daemon.\n\n");
                fprintf(stderr, "Usage: %s [options]\n\n", argv[0]);
                fprintf(stderr, "Options:\n");
                fprintf(stderr, "    -b FILE     i-MSCP backend script path\n");
                fprintf(stderr, "    -f FILE     Pid file path\n");
                fprintf(stderr, "    -h          This help\n");
                exit(EXIT_FAILURE);
        }
    }

    if(backendscriptpath == NULL) {
        fprintf(stderr, "Missing i-MSCP backend script path option\n");
        exit(EXIT_FAILURE);
    }

    /* setup pipe for notification */
    if(pipe(notify_pipe) == -1) {
        perror("Couldn't create pipe for notification");
        exit(EXIT_FAILURE);
    }

    /* daemonize */
    daemon_init();

    /* daemon process */

    {
        int reuse = 1;
        int servsockfd, clisockfd;
        struct sockaddr_in servaddr;
        struct sockaddr_in cliaddr;
        struct timeval timeout_rcv, timeout_snd;
        socklen_t clilen;

        if((servsockfd = socket(AF_INET, SOCK_STREAM, IPPROTO_IP)) < 0) {
            say(message(MSG_ERROR_SOCKET_CREATE), strerror(errno));
            notify(-1);
        }

#ifdef SO_REUSEPORT
    /*
        Even if defined, SO_REUSEPORT could be unsupported. Thus we just ignore error if errno is equal to ENOPROTOOPT
        See http://man7.org/linux/man-pages/man2/setsockopt.2.html
    */
    if(setsockopt(servsockfd, SOL_SOCKET, SO_REUSEPORT, (const char*)&reuse, sizeof(reuse)) < 0 && errno != ENOPROTOOPT) {
        say(message(MSG_ERROR_SOCKET_OPTION), strerror(errno));
            close(servsockfd);
            notify(-1);
        }
#endif

#ifdef SO_REUSEADDR
    if(setsockopt(servsockfd, SOL_SOCKET, SO_REUSEADDR, (const char*)&reuse, sizeof(reuse)) < 0) {
        say(message(MSG_ERROR_SOCKET_OPTION), strerror(errno));
        close(servsockfd);
        notify(-1);
    }
#endif

        /* ident socket */
        memset((void *) &servaddr, '\0', (size_t) sizeof(servaddr));
        servaddr.sin_family = AF_INET;
        servaddr.sin_addr.s_addr = htonl(DAEMON_LISTEN_ADDR);
        servaddr.sin_port = htons(DAEMON_LISTEN_PORT);

        /* assign name to the socket */
        if (bind(servsockfd, (struct sockaddr *) &servaddr, sizeof(servaddr)) < 0) {
            say(message(MSG_ERROR_BIND), strerror(errno));
            notify(-1);
        }

        /* marks the socket referred to by servsockfd as a passive socket */
        if (listen(servsockfd, DAEMON_MAX_LISTENQ) < 0) {
            say(message(MSG_ERROR_LISTEN), strerror(errno));
            notify(-1);
        }

        /* setup timeout for input operations  */
        timeout_rcv.tv_sec = 10;
        timeout_rcv.tv_usec = 0;

        /* setup timeout for output operations */
        timeout_snd.tv_sec = 10;
        timeout_snd.tv_usec = 0;

        /* FIXME: use sigaction(2) */
        signal(SIGCHLD, handle_signal);
        signal(SIGPIPE, handle_signal);

        /* write pidfile if needed */
        if(pidfile != NULL) {
            FILE *file = fopen(pidfile, "w");
            fprintf(file, "%ld", (long)getpid());
            fclose(file);
        }

        free(pidfile);

        /* notify parent process that initialization is done and that pidfile has been written */
        notify(0);

        say("%s", message(MSG_DAEMON_STARTED));

        while (1) {
            memset((void *) &cliaddr, '\0', sizeof(cliaddr));
            clilen = (socklen_t) sizeof(cliaddr);

            /* Wait for new client connection */
            if ((clisockfd = accept(servsockfd, (struct sockaddr *) &cliaddr, &clilen)) < 0) {
                if (errno == EINTR) {
                    continue;
                }

                say(message(MSG_ERROR_ACCEPT), strerror(errno));
                free(backendscriptpath);
                close(servsockfd);
                exit(errno);
            }

            if(setsockopt(clisockfd, SOL_SOCKET, SO_RCVTIMEO, (char *)&timeout_rcv, sizeof(timeout_rcv)) < 0
                || setsockopt(clisockfd, SOL_SOCKET, SO_SNDTIMEO, (char *)&timeout_snd, sizeof(timeout_snd)) < 0
            ) {
                say(message(MSG_ERROR_SOCKET_OPTION), strerror(errno));
                free(backendscriptpath);
                close(clisockfd);
                exit(errno);
            }

            if (fork() == 0) {
                close(servsockfd);
                say("%s", message(MSG_START_CHILD));
                handle_client_connection(clisockfd, (struct sockaddr *) &cliaddr);
                free(backendscriptpath);
                close(clisockfd);
                say("%s", message(MSG_END_CHILD));
                exit(EXIT_SUCCESS);
            }

            close(clisockfd);
        }

        close(servsockfd);
    }

    free(backendscriptpath);
    closelog();
    return 0;
}
