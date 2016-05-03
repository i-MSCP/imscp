#include "daemon_notify.h"

void notify(int status)
{
    int writeval;

    if(status == -1) {
        writeval = 0;
        say("%s", "Sending \"0\" (error) to parent via notification pipe");
    } else {
        writeval = 1;
        say("%s", "Sending \"1\" (OK) to parent via notification pipe");
    }

    write(notify_pipe[1], &writeval, sizeof(writeval));
    close(notify_pipe[1]);

    if(status == -1) {
        exit(EXIT_FAILURE);
    }
}
