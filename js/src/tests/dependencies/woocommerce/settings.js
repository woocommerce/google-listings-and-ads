export default function getSettings() {
	return {};
}

export function getSetting( key, backup ) {
	return global.wcSettings[ key ] || backup;
}
