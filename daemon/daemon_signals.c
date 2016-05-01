#include "daemon_signals.h"

void sig_child (int signo)
{
    pid_t pid;
    int status;
    char *nmb = (char *) calloc(50, sizeof(char));

    while (( pid = waitpid(-1, &status, WNOHANG)) > 0) {
        sprintf(nmb, "%d", pid);
        memset((void *) nmb, '\0', 50);
    }

    free(nmb);
    signal(SIGCHLD, sig_child);
}

void sig_pipe(int signo)
{
    say("%s", message(MSG_SIG_PIPE));
    signal(SIGPIPE, sig_pipe);
}
