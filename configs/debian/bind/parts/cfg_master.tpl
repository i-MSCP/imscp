zone "{DOMAIN_NAME}" {
  type master;
  file "{DB_DIR}/{DOMAIN_NAME}.db";
  allow-transfer { {SECONDARY_DNS} };
  notify yes;
};
