#include "daemon_init.h"

void daemon_init(void)
{
    pid_t pid;
    int fd, maxfd;

    /*
     * The calling process (parent process) will die soon
     * and the daemon process continues to initialize itself.
     *
     * The parent process has then to wait for the daemon process
     * to initialize to return a consistent exit value. For this
     * purpose, the daemon process will send \"1\" into the pipe if
     * everything went well and \"0\" otherwise.
     */
    switch(fork()) {
        case -1:
            perror("failed to daemonize");
            exit(EXIT_FAILURE);
        case 0: /* child process */
            close(notify_pipe[0]); /* close the read side of the pipe */
            break;
        default: { /* parent process */
            struct timeval tv;
            fd_set rfds;
            int ret;
            int readval;

            close(notify_pipe[1]); /* close the write side of the pipe */

            /*
             * wait for 10s before exiting with error
             * the daemon process is supposed to send 1 or 0 into the pipe to tell the parent
             * how it goes for it
             */

            FD_ZERO(&rfds);
            FD_SET(notify_pipe[0], &rfds);

            tv.tv_sec = 10;
            tv.tv_usec = 0;

            ret = select(notify_pipe[0] + 1, &rfds, NULL, NULL, &tv);
            if (ret == -1) {
                perror("failed to select");
                exit(EXIT_FAILURE);
            }

            if (!ret) { /* no data received */
                perror("the daemon process didn't send back its status (via the pipe to the calling process) in the expected time");
                exit(EXIT_FAILURE);
            }

            ret = read(notify_pipe[0], &readval, sizeof(readval));
            if (ret == -1) {
                perror("failed to read from pipe");
                exit(EXIT_FAILURE);
            }

            if (ret == 0) {
                fprintf(stderr, "no data have been read from pipe\n");
                exit(EXIT_FAILURE);
            }

            if (readval == 1) {
                exit(EXIT_SUCCESS);
            }

            fprintf(stderr, "the daemon process returned an error!\n");
            exit(EXIT_FAILURE);
        }
    }

    /* continue as a child */

    if(setsid() == -1) {
        perror("Couldn't setsid()");
        notify(-1);
    }

    /* ignore signal sent from child to parent process */
    signal(SIGCHLD, SIG_IGN);

    /* Fork off for the second time */
    pid = fork();

    if(pid == -1) {
        perror("failed to daemonize");
        notify(-1);
    }

     /* success; let the first child terminate */
    if(pid > 0) {
        exit(EXIT_SUCCESS);
    }

    /* continue as daemon process */

    /* set new file permissions */
    if(umask(0) == -1) {
        perror("Couldn't umask()");
        notify(-1);
    }

    /* change working directory to root directory */
    if(chdir("/") == -1) {
        perror("Couldn't chdir()");
        notify(-1);
    }

    /* Close all open file descriptors except the write side of our notification pipe */
    maxfd = sysconf(_SC_OPEN_MAX);
    for(fd = 0; fd < maxfd; fd++) {
        if(fd != notify_pipe[1])
            close(fd);
    }

    /* Reopen stdin (fd = 0), stdout (fd = 1), stderr (fd = 2) */
    stdin = fopen("/dev/null", "r");
    stdout = fopen("/dev/null", "w+");
    stderr = fopen("/dev/null", "w+");

    /* open log */
    openlog(message(MSG_DAEMON_NAME), LOG_PID, SYSLOG_FACILITY);
}
