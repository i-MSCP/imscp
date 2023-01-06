zone "{DOMAIN_NAME}" {
  type master;
  masterfile-format {BIND_DB_FORMAT};
  file "imscp/master/{DOMAIN_NAME}.db";
  allow-transfer { {SECONDARY_DNS} };
  notify yes;
};
