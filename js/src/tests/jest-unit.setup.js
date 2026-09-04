// Fix the "addEventListener is not a function" issue caused by `@wordpress/viewport`.
// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/viewport%404.20.0/packages/viewport/src/listener.js#L44
const actualMatchMedia = global.window.matchMedia;

global.window.matchMedia = function ( mediaQueryString ) {
	const mediaQueryList = actualMatchMedia.call( this, mediaQueryString );
	mediaQueryList.addEventListener = mediaQueryList.addListener;
	return mediaQueryList;
};

// Fix "ResizeObserver is not defined" issue caused by `@floating-ui/dom`,
// which is one of `@wordpress/components@19.17.0`'s dependencies.
global.ResizeObserver = jest
	.fn()
	.mockName( 'ResizeObserver' )
	.mockImplementation( () => ( {
		observe: jest.fn().mockName( 'resizeObserver.observe' ),
		unobserve: jest.fn().mockName( 'resizeObserver.unobserve' ),
		disconnect: jest.fn().mockName( 'resizeObserver.disconnect' ),
	} ) );

global.wpNavMenuClassChange = jest.fn().mockName( 'wpNavMenuClassChange' );

// The data store of 'core/notices' namespace is registered by the `@wordpress/notices`
// in WordPress Core in runtime. It's always unavailable when running jest so here it's
// mocked globally.
//
// Ref: https://github.com/WordPress/gutenberg/tree/trunk/packages/notices#usage
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest
		.fn()
		.mockName( 'useDispatchCoreNotices' )
		.mockReturnValue( { createNotice: () => {} } )
);

// Avoid tests that depend on `handleError` utils needing to mock global.console individually.
// To test real console calls, please refer to ~/utils/handleError.test.js
jest.mock( '~/utils/console', () => ( {
	logError: jest.fn().mockName( 'console.error' ),
} ) );

// `jsdom` doesn't provide a global `Response` (unlike a real browser or Node's own global
// scope), but `~/utils/extractDetailedApiError` does `responseOrError instanceof Response` — so
// any error rejected in a test that goes through it needs `Response` to exist as a global.
if ( typeof global.Response === 'undefined' ) {
	global.Response = class Response {};
}

// Mock the glaData here because the `globals` object in the jest config must
// be JSON-serializable, which cannot preserve `undefined` values.
global.glaData = {
	slug: 'gla',
	mcSetupComplete: true,
	mcSupportedCountry: true,
	mcSupportedLanguage: true,
	adsSetupComplete: true,
	enableReports: true,
	dateFormat: 'F j, Y',
	timeFormat: 'g:i a',
	initialWpData: {
		// Intentionally set `version` to undefined so that tests related to event
		// tracking don't have to validate global event properties.
		version: undefined,
	},
};
