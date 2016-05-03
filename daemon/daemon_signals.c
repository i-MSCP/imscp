#include "daemon_signals.h"

void handle_signal(int signo)
{
    switch(signo) {
        case SIGPIPE:
            say("%s", message(MSG_SIG_PIPE));
            break;
        case SIGCHLD: {
            int stat;
            while (waitpid(-1, &stat, WNOHANG) > 0);
        }
    }

    signal(signo, handle_signal);
}
