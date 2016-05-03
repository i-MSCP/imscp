#include "daemon_notify.h"

void notify(int status)
{
    int writeval;

    if(status == -1) {
        writeval = 0;
        say("Sending \"0\" (error) to parent via fd=%s", (char *)&notify_pipe[1]);
    } else {
        writeval = 1;
        say("Sending \"1\" (OK) to parent via fd=%s",  (char *)&notify_pipe[1]);
    }

    write(notify_pipe[1], &writeval, sizeof(writeval));
    close(notify_pipe[1]);

    if(status == -1) {
        exit(EXIT_FAILURE);
    }
}
