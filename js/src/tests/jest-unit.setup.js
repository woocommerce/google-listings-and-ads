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
