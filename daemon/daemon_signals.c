#include "daemon_signals.h"

void handle_signal(int signo)
{
    if(signo == SIGPIPE) {
        say("%s", message(MSG_SIG_PIPE));
    } else if(signo == SIGCHLD) {
        pid_t pid;
        int status;
        char *nmb = (char *) calloc(50, sizeof(char));

        while (( pid = waitpid(-1, &status, WNOHANG)) > 0) {
            sprintf(nmb, "%d", pid);
            memset((void *) nmb, '\0', 50);
        }

        free(nmb);
    }

    signal(signo, handle_signal);
}
