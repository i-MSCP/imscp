
#include "receive_data.h"

int receive_data(int fd, char *dest, size_t n)
{
    ssize_t i, res;

    char c, *p;

    p = dest;

    for (i = 1; i <= n; i++) {

    try_again:

	if ((res = read(fd, &c, 1)) == 1) {

	    *p++ = c;

	    if (c == '\n')
		break;

	} else if (res == 0) { /* EOF, arrived ! */

	    if (i == 1)
		return (0); /* no data read. */
	    else
		break; /* some data was read. */

	} else {
	    if (errno == EINTR) {
                say("%s", message(MSG_ERROR_EINTR));
		goto try_again;
	    }
	    return (-1);
	}
    }

    *p = 0;

    return (i);
}
