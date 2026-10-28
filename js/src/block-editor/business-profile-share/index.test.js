jest.mock(
	'@wordpress/plugins',
	() => ( {
		registerPlugin: jest.fn(),
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/editor',
	() => ( {
		PluginDocumentSettingPanel: () => null,
	} ),
	{ virtual: true }
);

/**
 * The `glaData` the block editor receives from PHP, rather than the full object the plugin's own
 * admin pages get, so the entry is loaded the way it is on the post screen.
 */
const POST_SCREEN_GLA_DATA = {
	slug: 'gla',
	initialWpData: {
		version: '1.0.0',
		mcId: null,
		adsId: null,
	},
};

describe( 'Business Profile share panel entry', () => {
	const originalGlaData = global.glaData;

	afterEach( () => {
		global.glaData = originalGlaData;
	} );

	it( 'registers the panel with the glaData the block editor receives', () => {
		global.glaData = POST_SCREEN_GLA_DATA;

		// Load the entry in a fresh module registry, so it reads this glaData rather than the one
		// the test setup defines.
		let registerPlugin;
		jest.isolateModules( () => {
			( { registerPlugin } = require( '@wordpress/plugins' ) );
			require( './index' );
		} );

		expect( registerPlugin ).toHaveBeenCalledWith(
			'google-listings-and-ads-business-profile-share',
			expect.objectContaining( { render: expect.any( Function ) } )
		);
	} );
} );
