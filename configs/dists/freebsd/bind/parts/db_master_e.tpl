$TTL 86400
@	IN	SOA	ns.{DMN_NAME}. root.{DMN_NAME}. (
; dmn [{DMN_NAME}] timestamp entry BEGIN.
			{TIMESTAMP}
; dmn [{DMN_NAME}] timestamp entry END.
			8H
			2H
			4W
			1D
)
		IN	NS	ns1.{DMN_NAME}.
		IN	NS	ns2.{DMN_NAME}.
		IN	MX	10 mail.{DMN_NAME}.

{DMN_NAME}.	IN	A	{DMN_IP}
{DMN_NAME}.	IN	TXT	"v=spf1 a mx ip4:{DMN_IP} ~all"
ns1		IN	A	{BASE_SERVER_IP}
ns2		IN	A	{SECONDARY_DNS_IP}
mail		IN	A	{BASE_SERVER_IP}
localhost	IN	A	127.0.0.1
webmail	CNAME	{DMN_NAME}.
ftp		CNAME	{DMN_NAME}.
; sub [{SUB_NAME}] entry BEGIN.
; sub [{SUB_NAME}] entry END.