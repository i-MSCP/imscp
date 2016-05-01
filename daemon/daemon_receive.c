#include "daemon_receive.h"

int receive_data(int fd, char *dest, size_t n)
{
    ssize_t i, rs;
    char c, *p;
    p = dest;

    for (i = 1; i <= n; i++) {
        try_again:

        if ((rs = read(fd, &c, 1)) == 1) {
            *p++ = c;

            if (c == '\n') {
                break;
            }
        } else if (rs == 0) { /* EOF, arrived ! */
            if (i == 1) { /* no data read. */
                return 0;
            }
            /* some data was read. */
            break;
        } else {
            if (errno == EINTR) {
                goto try_again;
            }

            return -1;
        }
    }

    *p = 0;
    return i;
}


int receive_line(int fd, char *dest, size_t n)
{
    int rs;

    if ((rs = receive_data(fd, dest, n)) < 0) {
        say(message(MSG_ERROR_SOCKET_RD), strerror(errno));
        return -1;
    }

    if (rs == 0) {
        say("%s", message(MSG_ERROR_SOCKET_EOF));
    }

    return rs;
}
