'use strict';
module.exports = {
	port: 5500,
	host: 'localhost',
	connectServer:
		process.env.WOOCOMMERCE_CONNECT_SERVER ||
		'https://api-vipgo.woocommerce.com',
	logResponses: process.env.PROXY_LOG_RESPONSES || false,
};
