zone "{DMN_NAME}" {
	type slave;
	file "{DB_DIR}/slave/{DMN_NAME}.db";
	masters	{ {PRIMARY_DNS} };
};
