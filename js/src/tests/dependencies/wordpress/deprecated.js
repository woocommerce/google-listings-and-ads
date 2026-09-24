// Wraps `@wordpress/deprecated` for Jest so that known deprecation notices, which the
// plugin hasn't opted out of yet, don't fail tests via `@wordpress/jest-console`.
// It's mapped by `moduleNameMapper`, so it covers every nested copy of the package too.
//
// These notices are also shown in WordPress 6.9 at runtime, as `@wordpress/components`
// is externalized. Opting into the new styles is a visual change tracked separately.
const actual = jest.requireActual(
	'../../../../../node_modules/@wordpress/deprecated'
);

const IGNORED_NOTICES = [
	// `@wordpress/components` 36px default size, removed in WordPress 7.1.
	/^36px default size for wp\.components\.\w+$/,
	// `@wordpress/components` bottom margin styles, removed in WordPress 7.0.
	/^Bottom margin styles for wp\.components\.\w+$/,
	// Used by `Tooltip` of `@woocommerce/components`, not by this plugin.
	/^`position` prop in wp\.components\.tooltip$/,
];

function deprecated( feature, options ) {
	if ( IGNORED_NOTICES.some( ( pattern ) => pattern.test( feature ) ) ) {
		return;
	}

	return actual.default( feature, options );
}

module.exports = {
	...actual,
	__esModule: true,
	default: deprecated,
};
