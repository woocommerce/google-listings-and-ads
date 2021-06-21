const path = require( 'path' );
const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

// eslint-disable-next-line react-hooks/rules-of-hooks
const config = useE2EJestConfig( {
	moduleFileExtensions: [ 'js', 'ts' ],
	roots: [ path.resolve( __dirname, '../specs' ) ],
	testMatch: [ '**/*.(test|spec).(j|t)s', '*.(test|spec).(j|t)s' ],
	testTimeout: 30000,
} );

module.exports = config;
