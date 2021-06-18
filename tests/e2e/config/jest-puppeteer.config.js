const { useE2EJestPuppeteerConfig } = require( '@woocommerce/e2e-environment' );

// eslint-disable-next-line react-hooks/rules-of-hooks
const puppeteerConfig = useE2EJestPuppeteerConfig( {
	launch: {
		browserContext: 'incognito',
		args: [ '--incognito', '--window-size=1920,1080' ],
		defaultViewport: {
			width: 1280,
			height: 800,
		},
	},
} );

module.exports = puppeteerConfig;
