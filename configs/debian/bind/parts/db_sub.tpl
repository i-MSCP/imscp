; sub [{SUBDOMAIN_NAME}] entry BEGIN
$ORIGIN {SUBDOMAIN_NAME}.
; sub MX entry BEGIN
@	IN	MX	{MX_DATA}
; sub MX entry ENDING
; sub SPF entry BEGIN
@	IN	TXT	"v=spf1 include:{DOMAIN_NAME} ~all"
@	IN	SPF	"v=spf1 include:{DOMAIN_NAME} ~all"
; sub SPF entry ENDING
@	IN	{IP_TYPE}	{DOMAIN_IP}
www	IN	CNAME	@
ftp	IN	{IP_TYPE}	{DOMAIN_IP}
; sub [{SUBDOMAIN_NAME}] entry ENDING
