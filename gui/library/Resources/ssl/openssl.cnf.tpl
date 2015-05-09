# i-MSCP openssl.cnf template file for self-signed certificates

[req]
distinguished_name = req_distinguished_name
default_bits = 2048
default_md = sha256
default_days = 365
x509_extensions = v3_self_signed
string_mask = utf8only

[req_distinguished_name]

[v3_self_signed]
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer:always
basicConstraints = critical,CA:FALSE
keyUsage = keyCertSign, nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @alt_names
issuerAltName = issuer:copy

[alt_names]
DNS.1 = {ADMIN_SYS_NAME}.{BASE_SERVER_VHOST}
DNS.2 = www.{DOMAIN_NAME}
