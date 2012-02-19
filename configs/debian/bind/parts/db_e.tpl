$TTL 12H
$ORIGIN {DMN_NAME}.
@               IN              SOA             ns1.{DMN_NAME}. postmaster.{DMN_NAME}. (
; dmn [{DMN_NAME}] timestamp entry BEGIN.
                {TIMESTAMP}     ; Serial
; dmn [{DMN_NAME}] timestamp entry END.
                8H              ; Refresh
                2H              ; Retry
                7D              ; Expire
                1D              ; Minimum TTL
)
; ns DECLARATION SECTION BEGIN
                IN              NS              ns{NS_NUMBER}.{DMN_NAME}.
; ns DECLARATION SECTION END

{MX}                IN              MX      10      mail.{DMN_NAME}.

{DMN_NAME}.     IN              {IP_TYPE}               {DMN_IP}
www             IN              {IP_TYPE}               {DMN_IP}
{MX}{DMN_NAME}.     IN              TXT             "v=spf1 a mx {TXT_DMN_IP_TYPE}:{DMN_IP} {TXT_SERVER_IP_TYPE}:{BASE_SERVER_IP} ~all"
localhost       IN              A               127.0.0.1
{MX}mail            IN              {IP_TYPE}               {DMN_IP}
; ns A SECTION BEGIN
ns{NS_NUMBER}             IN              {NS_IP_TYPE}               {NS_IP}
; ns A SECTION END

; CNAME for mail transfer
{MX}imap            IN              CNAME           mail
{MX}pop             IN              CNAME           mail
{MX}pop3            IN              CNAME           mail
{MX}relay           IN              CNAME           mail
{MX}smtp            IN              CNAME           mail
; CNAME for web transfer
ftp             IN              CNAME           www

; sub [{SUB_NAME}] entry BEGIN.
; sub [{SUB_NAME}] entry END.

; dns [{MANUAL_DNS_ID}] entry BEGIN.
; dns [{MANUAL_DNS_ID}] entry END.

; ctm domain als entries BEGIN.
; ctm domain als entries END.
