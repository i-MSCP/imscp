zone "{DOMAIN_NAME}" {
  type slave;
  masterfile-format {BIND_DB_FORMAT};
  file "imscp/slave/{DOMAIN_NAME}.db";
  masters { {PRIMARY_DNS} };
};
