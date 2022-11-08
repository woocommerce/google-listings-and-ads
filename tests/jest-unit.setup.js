const previousMatchMedia = global.window.matchMedia;
global.window.matchMedia = () => ( {
	...previousMatchMedia(),
	addEventListener: 'foo', // () => {}, // breaks jest transforms ¯\_(ツ)_/¯
	removeEventListener: 'foo', // () => {},
} );
