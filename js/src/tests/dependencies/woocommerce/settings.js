export default function getSettings() {
	return {};
}

export function getSetting( key, backup ) {
	return global.wcSettings[ key ] || backup;
}

// This mock function is used for jest testing only and therefore doesn't
// implement the actual comparison logic. Whenever any implementation relies on
// `isWpVersion`, add the corresponding return value when writing tests for it.
// Jest is currently running tests with npm packages bundled with WordPress 6.4.
export function isWpVersion( version, operator ) {
	if ( version === '6.4' && operator === '<' ) {
		return false;
	}

	// eslint-disable-next-line no-console
	console.warn(
		`Please specify the mocking result of \`isWpVersion( '${ version }', '${ operator }' )\` for your test case.`
	);
}
