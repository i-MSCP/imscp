zone "{DOMAIN_NAME}" {
  type slave;
  file "{DB_DIR}/slave/{DOMAIN_NAME}.db";
  masters { {PRIMARY_DNS} };
};
