export default function getSettings() {
	return {};
}

export function getSetting( key, backup ) {
	return global.wcSettings[ key ] || backup;
}

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
