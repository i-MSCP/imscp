
#include "recv_line.h"

int recv_line(int fd, char *dest, size_t n)
{
    int res;

    if ((res = receive_data(fd, dest, n)) < 0) {

        say(message(MSG_ERROR_SOCKET_RD), strerror(errno));

        return (-1);

    } else if (res == 0) {

        say("%s", message(MSG_ERROR_SOCKET_EOF));

    } else {
        /*char *nmb = calloc(10, sizeof(char));

        sprintf(nmb, "%d", res);

        say(message(MSG_BYTES_READ), nmb);

        free(nmb);*/
    }

    return (res);
}
