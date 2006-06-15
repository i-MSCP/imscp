$TTL 86400
@	IN	SOA	{DMN_NAME}. root.{DMN_NAME}. (
; dmn [{DMN_NAME}] timestamp entry BEGIN.
			 {TIMESTAMP}	
; dmn [{DMN_NAME}] timestamp entry END.
			 8H	
			 2H	
			 4W	
			 1D )	
		 IN 	 NS 	 ns.{DMN_NAME}.
; dmn [{DMN_NAME}] dns2 entry BEGIN.
; dmn [{DMN_NAME}] dns2 entry END.
		 IN 	 MX 	 10 mail.{DMN_NAME}.
                 
{DMN_NAME}.	A	{DMN_IP}
ns		IN	A	{DMN_IP}
mail		IN	A	{DMN_IP}
www		CNAME	{DMN_NAME}.
ftp		CNAME	{DMN_NAME}.
; sub [{SUB_NAME}] entry BEGIN.
; sub [{SUB_NAME}] entry END.
