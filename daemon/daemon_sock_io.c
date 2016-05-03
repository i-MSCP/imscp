#include "daemon_sock_io.h"

int read_data(int sockfd, char *buffer, size_t n)
{
    ssize_t i, retval;
    char c, *p;
    p = buffer;

    for (i = 1; i <= n; i++) {
        try_again:

        if ((retval = read(sockfd, &c, 1)) == 1) {
            *p++ = c;

            if (c == '\n') {
                break;
            }
        } else if (retval == 0) { /* EOF, arrived ! */
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

    *p = '\0';
    return i;
}

int read_line(int sockfd, char *buffer, size_t n)
{
    int retval;

    retval = read_data(sockfd, buffer, n);

    if (retval == -1) {
        if(errno == EAGAIN) {
            say("%s", "Connection timeout. Aborting.");
        } else {
            say(message(MSG_ERROR_SOCKET_RD), strerror(errno));
        }

        return -1;
    }

    if (retval == 0) {
        say("%s", message(MSG_ERROR_SOCKET_EOF));
        return -1;
    }

    return 0;
}

int write_data(int sockfd, char *src, size_t n)
{
    char *p;
    ssize_t retval;
    size_t i = 0;
    p = src;

    while (i < n) {
        if ((retval = write(sockfd, p, 1)) <= 0) {
            if (retval != EINTR) {
                return -1;
            }
        } else {
            p++;
            i++;
        }
    }

    return i;
}

int write_line(int sockfd, char *src, size_t n)
{
    if (write_data(sockfd, src, n) == -1) {
        if(errno == EAGAIN) {
            say("%s", "Connection timeout. Aborting.");
        } else {
            say(message(MSG_ERROR_SOCKET_WR), strerror(errno));
        }

        return -1;
    }

    return 0;
}
