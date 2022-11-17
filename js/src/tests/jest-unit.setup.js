// Fix the "addEventListener is not a function" issue caused by `@wordpress/viewport`.
// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/viewport%404.20.0/packages/viewport/src/listener.js#L44
const actualMatchMedia = global.window.matchMedia;

global.window.matchMedia = function ( mediaQueryString ) {
	const mediaQueryList = actualMatchMedia.call( this, mediaQueryString );
	mediaQueryList.addEventListener = mediaQueryList.addListener;
	return mediaQueryList;
};
