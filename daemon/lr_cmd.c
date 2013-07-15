#include "lr_cmd.h"

int lrCommand(int fd, char *msg)
{
	return lrSyntax(fd, msg);
}
