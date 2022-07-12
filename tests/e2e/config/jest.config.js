const path = require( 'path' );
const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

// eslint-disable-next-line react-hooks/rules-of-hooks
const config = useE2EJestConfig( {
	moduleFileExtensions: [ 'js', 'ts' ],
	roots: [
		path.resolve( __dirname, '../specs' ),
		// Execute `admin-e2e-tests` directly from node_modules.
		path.resolve(
			__dirname,
			'../../../node_modules/@woocommerce/admin-e2e-tests'
		),
	],
	testMatch: [ '**/*.(test|spec).(j|t)s', '*.(test|spec).(j|t)s' ],
	testTimeout: 30000,
	haste: {
		retainAllFiles: true,
	},
	transformIgnorePatterns: [
		'node_modules/(?!@woocommerce/admin-e2e-tests/.*)',
	],
	testPathIgnorePatterns: [
		'node_modules/(?!@woocommerce/admin-e2e-tests/.*)',
	],
} );

module.exports = config;
