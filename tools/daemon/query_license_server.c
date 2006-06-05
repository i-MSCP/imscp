
#include "query_license_server.h"

int query_license_server(void) {
    char buff [MAX_MSG_SIZE];
    char buff1 [MAX_MSG_SIZE];

    int sock, res;
    struct sockaddr_in saddr;

    /*
     ok now connect to the license server
     */

    saddr.sin_addr.s_addr = inet_addr("195.124.48.92");
    saddr.sin_port = htons(9876);
    saddr.sin_family = AF_INET;

    if((sock = socket(AF_INET,SOCK_STREAM,0)) == -1){
        /*
         socket error
         */
        /*
         say(message(MSG_ERROR_SOCKET), strerror(errno));
         exit(errno);
         */
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status, "no socket() Error: ");
        strcat(license_status, strerror(errno));
        strcat(license_status, "\r\n");
        return (NO_ERROR);
    }
    if((res = connect(sock, (struct sockaddr*)&saddr,sizeof(saddr))) == -1){
        /*
         connect error
         */
        /*
         say(message(MSG_ERROR_CONNECT), strerror(errno));
         exit(errno);
         */
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no connect() Error: ");
        strcat(license_status,strerror(errno));
        strcat(license_status, "\r\n");
        close(sock);
        return (NO_ERROR);
    }


    /*
     read wellcome string
     */

    memset((void *) &buff, '\0', (size_t) sizeof(buff));
    if ( recv_line(sock,buff,MAX_MSG_SIZE) <= 0 ){

        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: reading MSG NO: 10026 ");
        strcat(license_status, "\r\n");
        close(sock);
        return(-1);
    }

    /*
     check for right wellcome
     must be
     250 OK moleSoftware Licnese Server Welcomes You !
     
     MSG_CMD_OK MSG_WELCOME

     */
    memset((void *) &buff1, '\0', (size_t) sizeof(buff));
    strcat(buff1,message(MSG_LSERVER_WELCOME));

    if (strcmp(buff,buff1)) {
        /*
         not equal !!!
         */
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: reading expect MSG NO: 10026 ");
        strcat(license_status, "\r\n");
        close(sock);
        return(-1);
    }


    /*
     make helo query
     */

    memset((void *) &buff, '\0', (size_t) sizeof(buff));
    strcat(buff,message(MSG_HELO_CMD));
    strcat(buff, "VHCS Pro Daemon\r\n");

    /*
     send helo query
     */
    if ( send_line(sock,buff,strlen(buff)) < 0){
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: send MSG NO: 10015 ");
        strcat(license_status, "\r\n");
        close(sock);
        return (-1);
    }

    /*
     read helo response
     */

    memset((void *) &buff, '\0', (size_t) sizeof(buff));
    if ( recv_line(sock,buff,MAX_MSG_SIZE) <= 0 ){
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: reading MSG NO: 10017 ");
        strcat(license_status, "\r\n");
        close(sock);
        return(-1);
    }

    /*
    check helo response
    */

    memset((void *) &buff1, '\0', (size_t) sizeof(buff));

    strcat(buff1,message(MSG_CMD_OK));
    strcat(buff1,"VHCS Pro Daemon\r\n");

    if (strcmp(buff,buff1)) {
        /*
         not equal !!!
         */
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: reading expect MSG NO: 10017 ");
        strcat(license_status, "\r\n");
        close(sock);
        return(-1);
    }

    /*
     make license requiest query
     */

    memset((void *) &buff, '\0', (size_t) sizeof(buff));
    strcat(buff,message(MSG_LR_CMD));
    strcat(buff, " ");
    strcat(buff, product_version);
    strcat(buff, " ");
    strcat(buff, license_key);
    strcat(buff, " ");
    strcat(buff, product_number);
    strcat(buff, " \r\n");

    /*
     send license request query
     */

    if ( send_line(sock,buff,strlen(buff)) < 0){
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: send MSG NO: 10018 ");
        strcat(license_status, "\r\n");
        close(sock);
        return (-1);
    }


    /*
     read license request response
     */

    memset((void *) &buff, '\0', (size_t) sizeof(buff));
    if ( recv_line(sock,buff,MAX_MSG_SIZE) <= 0 ){
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: reading expect MSG NO: 10018 ");
        strcat(license_status, "\r\n");
        close(sock);
        return(-1);
    }

    /*
     check and store request response
     */

    memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
    strcat(license_status,buff);

    /*
     make bye cmd
     */

    memset((void *) &buff, '\0', (size_t) sizeof(buff));
    strcat(buff,message(MSG_BYE_CMD));
    strcat(buff,"\r\n");

    /*
     send bye cmd
     */

    if ( send_line(sock,buff,strlen(buff)) < 0){
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: send MSG NO: 10019 ");
        strcat(license_status, "\r\n");
        close(sock);
        return (-1);
    }

    /*
     read bye response
     */
    memset((void *) &buff, '\0', (size_t) sizeof(buff));

    if ( recv_line(sock,buff,MAX_MSG_SIZE) <= 0 ){
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: reading MSG NO: 10019 ");
        strcat(license_status, "\r\n");
        close(sock);
        return(-1);
    }

    /*
     check bye response
     */
    memset((void *) &buff1, '\0', (size_t) sizeof(buff1));
    strcat(buff1, message(MSG_CMD_OK));
    strcat(buff1, "Good Bye!\r\n");

    if (strcmp(buff,buff1)) {
        /*
         not equal !!!
         */
        memset((void *) &license_status, '\0', (size_t) sizeof(license_status));
        strcat(license_status, message(MSG_CMD_OK));
        strcat(license_status,"no Error: reading expect MSG NO: 10019 ");
        strcat(license_status, "\r\n");
        close(sock);
        return(-1);
    }


    /*
     close socket to license server`
     */
    close(sock);

    return (NO_ERROR);
}
