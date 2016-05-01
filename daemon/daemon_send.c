#include "daemon_send.h"

int send_data(int fd, char *src, size_t n)
{
    char *p;
    ssize_t rs;
    size_t i = 0;
    p = src;

    while (i < n) {
        if ((rs = write(fd, p, 1)) <= 0) {
            if (rs != EINTR) {
                return (-1);
            }
        } else {
            p++;
            i++;
        }
    }

    return i;
}

int send_line(int fd, char *src, size_t len)
{
    if (send_data(fd, src, len) < 0) {
        say(message(MSG_ERROR_SOCKET_WR), strerror(errno));
        return -1;
    }

    return 0;
}
