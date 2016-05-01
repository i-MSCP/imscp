#include "daemon_main.h"

int main(int argc, char **argv)
{
    /* parent process */

    int option;
    char *pidfile = NULL;

    /* Parse command line options */
    while ((option = getopt(argc, argv, "hb:p:")) != -1) {
        switch(option) {
            case 'b':
                backendscriptpath = strdup(optarg);
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
        perror("Could not create pipe for notification");
        exit(EXIT_FAILURE);
    }

    /* daemonize */
    daemon_init();

    /* daemon process */

    {
        int listenfd, connfd;
        struct sockaddr_in servaddr, cliaddr;
        struct timeval timeout_rcv, timeout_snd;
        struct linger so_linger;
        pid_t childpid;
        socklen_t clilen;

        /* Creates an endpoint for communication */
        if((listenfd = socket(AF_INET, SOCK_STREAM, IPPROTO_IP)) < 0) {
            say(message(MSG_ERROR_SOCKET_CREATE), strerror(errno));
            notify(-1);
        }

        /* Ident socket */
        memset((void *) &servaddr, '\0', (size_t) sizeof(servaddr));
        servaddr.sin_family = AF_INET;
        servaddr.sin_addr.s_addr = htonl(DAEMON_LISTEN_ADDR);
        servaddr.sin_port = htons(DAEMON_LISTEN_PORT);

        /* Assign name to the socket */
        if (bind(listenfd, (struct sockaddr *) &servaddr, sizeof(servaddr)) < 0) {
            say(message(MSG_ERROR_BIND), strerror(errno));
            notify(-1);
        }

        /* Marks the socket referred to by listenfd as a passive socket */
        if (listen(listenfd, DAEMON_MAX_LISTENQ) < 0) {
            say(message(MSG_ERROR_LISTEN), strerror(errno));
            notify(-1);
        }

        /* Setup timeout for input operations  */
        timeout_rcv.tv_sec = 10;
        timeout_rcv.tv_usec = 0;

        /* Setup timeout for output operations */
        timeout_snd.tv_sec = 10;
        timeout_snd.tv_usec = 0;

        /* Abort connection and discard any data immediately on close(2) */
        so_linger.l_onoff = 1;
        so_linger.l_linger = 0;

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

            /* Wait for new connection */
            if ((connfd = accept(listenfd, (struct sockaddr *) &cliaddr, &clilen)) < 0) {
                if (errno == EINTR) {
                    continue;
                }

                say(message(MSG_ERROR_ACCEPT), strerror(errno));
                exit(errno);
            }

            setsockopt(connfd, SOL_SOCKET, SO_RCVTIMEO, &timeout_rcv, sizeof(timeout_rcv));
            setsockopt(connfd, SOL_SOCKET, SO_SNDTIMEO, &timeout_snd, sizeof(timeout_snd));
            setsockopt(connfd, SOL_SOCKET, SO_LINGER, &so_linger, sizeof(so_linger));

            if ((childpid = fork()) == 0) {
                char *nmb = (char *) calloc(50, sizeof(char));

                close(listenfd);
                childpid = getpid();
                sprintf(nmb, "%d", childpid);
                say(message(MSG_START_CHILD), nmb);
                take_connection(connfd);
                say(message(MSG_END_CHILD), nmb);
                free(nmb);
                exit(EXIT_SUCCESS);
            }

            close(connfd);
        }
    }

    free(backendscriptpath);
    closelog();

    return 0;
}
