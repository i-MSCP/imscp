zone "{DMN_NAME}" {
	type	slave;
	file	"{DB_DIR}/{DMN_NAME}.db";
	masters	{ {PRIMARY_DNS} };
};
