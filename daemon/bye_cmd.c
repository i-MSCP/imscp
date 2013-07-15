#include "bye_cmd.h"

int byeCommand(int fd, char *msg)
{
	return byeSyntax(fd, msg);
}
