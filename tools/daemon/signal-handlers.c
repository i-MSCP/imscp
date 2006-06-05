
#include "signal-handlers.h"
    
void sig_child (int signo)
{
    pid_t pid;

    int stat;

    char *nmb = calloc(50, sizeof(char));

    /* say(message (MSG_SIG_CHLD), "BEFORE while()..."); */

    while ( ( pid = waitpid(-1, &stat, WNOHANG) ) > 0) {
	sprintf(nmb, "%d", pid);

	/* say(message(MSG_SIG_CHLD), nmb); */

        memset((void *) nmb, '\0', 50);
    }

    free(nmb);

    signal(SIGCHLD, sig_child);

    return;
}

void sig_pipe(int signo)
{
    say("%s", message(MSG_SIG_PIPE));

    signal(SIGPIPE, sig_pipe);

}
