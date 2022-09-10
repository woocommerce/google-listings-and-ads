'use strict';

const Hapi = require( '@hapi/hapi' );
const H2o2 = require( '@hapi/h2o2' );
const config = require( './config' );
const handler = require( './handler' );

const init = async () => {
	const server = Hapi.server( {
		port: config.port,
		host: config.host,
	} );
	await server.register( H2o2 );

	server.route( {
		method: '*',
		path: '/{path*}',
		config: {
			handler: ( request, h ) => {
				const response = handler.checkRequest( request );
				if ( response ) {
					if ( config.logResponses ) {
						// eslint-disable-next-line no-console
						console.log( 'Mocked response: ', response );
					}
					return response;
				}

				const proxy = {
					uri: `${ config.connectServer }/${ request.params.path }${
						request.url.search || ''
					}`,
					passThrough: true,
				};

				if ( config.logResponses ) {
					proxy.onResponse = ( err, res ) => {
						let body = '';

						res.on( 'data', ( chunk ) => {
							body += chunk;
						} );

						res.on( 'end', () => {
							// eslint-disable-next-line no-console
							console.log( 'API response: ', body );
						} );

						return res;
					};
				}

				return h.proxy( proxy );
			},
			payload: {
				parse: false,
			},
		},
	} );

	await server.start();

	// eslint-disable-next-line no-console
	console.log(
		'Proxy server running on %s > %s in %s mode',
		server.info.uri,
		config.connectServer,
		config.proxyMode
	);
};

init();
