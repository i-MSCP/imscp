zone "{DMN_NAME}" {
	type master;
	file "{DB_DIR}/{DMN_NAME}.db";
	allow-transfer { {SECONDARY_DNS} };
	notify yes;
};
