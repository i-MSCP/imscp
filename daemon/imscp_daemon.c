#include "imscp_daemon.h"

int main(int argc, char **argv)
{
	/* parent process */

	int listenfd, connfd, option;
	char pidfile[256];
	struct sockaddr_in servaddr, cliaddr;
	struct timeval timeout_rcv, timeout_snd;

	pid_t childpid;
	socklen_t clilen;

	/* Parse command line options */
	while ((option = getopt(argc, argv, "hb:p:")) != -1) {
		switch(option) {
			case 'b':
				if(strlen(optarg) > 255) {
					fprintf(stderr, "Backend script path too long, use under 255 characters\n");
					exit(EXIT_FAILURE);
				}
				strncpy(backendscriptpath, optarg, sizeof(backendscriptpath));
			break;
			case 'p':
				if(strlen(optarg) > 255) {
					fprintf(stderr, "Pid file path too long, use under 255 characters\n");
					exit(EXIT_FAILURE);
				}
				strncpy(pidfile, optarg, sizeof(pidfile));
			break;
			case 'h':
			default:
				fprintf(stderr, "i-MSCP Daemon.\n\n");
				fprintf(stderr, "Usage: %s [options]\n\n", argv[0]);
				fprintf(stderr, "Options:\n");
				fprintf(stderr, "    -b FILE     i-MSCP backend script path\n");
				fprintf(stderr, "    -f FILE     Pid file path\n");
				fprintf(stderr, "    -h          This help\n");

				exit(EXIT_FAILURE);
		}
	}

	if(backendscriptpath[0] == '\0') {
		fprintf(stderr, "Missing i-MSCP backend script path option\n");
		exit(EXIT_FAILURE);
	}

	/* setup pipe for notification */
	if(pipe(notification_pipe) == -1) {
		perror("Could not create pipe for notification");
		exit(EXIT_FAILURE);
	}

	/* daemonize */
	daemonInit();

	/* daemon process */

	/* Creates an endpoint for communication */
	if((listenfd = socket(AF_INET, SOCK_STREAM, IPPROTO_IP)) < 0) {
		say(message(MSG_ERROR_SOCKET_CREATE), strerror(errno));
		notify_parent(-1);
	}

	/* Ident socket */
	memset((void *) &servaddr, '\0', (size_t) sizeof(servaddr));
	servaddr.sin_family = AF_INET;
	servaddr.sin_addr.s_addr = htonl(DAEMON_LISTEN_ADDR);
	servaddr.sin_port = htons(DAEMON_LISTEN_PORT);

	/* Assign name to the socket */
	if (bind(listenfd, (struct sockaddr *) &servaddr, sizeof(servaddr)) < 0) {
		say(message(MSG_ERROR_BIND), strerror(errno));
		notify_parent(-1);
	}

	/* Marks the socket referred to by listenfd as a passive socket */
	if (listen(listenfd, DAEMON_MAX_LISTENQ) < 0) {
		say(message(MSG_ERROR_LISTEN), strerror(errno));
		notify_parent(-1);
	}

	/* Setup timeout for input operations  */
	timeout_rcv.tv_sec = 10;
	timeout_rcv.tv_usec = 0;

	/* Setup timeout for output operations */
	timeout_snd.tv_sec = 10;
	timeout_snd.tv_usec = 0;

	signal(SIGCHLD, sigChild);
	signal(SIGPIPE, sigPipe);

	/* write pidfile if needed */
	if(pidfile[0] != '\0') {
		FILE *file = fopen(pidfile, "w");
		fprintf(file, "%ld", (long)getpid());
		fclose(file);
	}

	/* notify parent process that initialization is done and that pidfile has been written */
	notify_parent(0);

	say("%s", message(MSG_DAEMON_STARTED));

	while (1) {
		memset((void *) &cliaddr, '\0', sizeof(cliaddr));
		clilen = (socklen_t) sizeof(cliaddr);

		/* Wait for new connection */
		if ((connfd = accept(listenfd, (struct sockaddr *) &cliaddr, &clilen)) < 0) {
			if (errno == EINTR) {
				continue;
			}

			say(message(MSG_ERROR_ACCEPT), strerror(errno));
			exit(errno);
		}

		setsockopt(connfd, SOL_SOCKET, SO_RCVTIMEO, (char *)&timeout_rcv, sizeof(timeout_rcv));
		setsockopt(connfd, SOL_SOCKET, SO_SNDTIMEO, (char *)&timeout_snd, sizeof(timeout_snd));

		if ((childpid = fork()) == 0) {
			char *nmb = calloc(50, sizeof(char));

			close(listenfd);
			childpid = getpid();
			sprintf(nmb, "%d", childpid);
			say(message(MSG_START_CHILD), nmb);
			takeConnection(connfd);
			say(message(MSG_END_CHILD), nmb);
			free(nmb);
			exit(EXIT_SUCCESS);
		}

		close(connfd);
	}

	closelog();
	return 0;
}
