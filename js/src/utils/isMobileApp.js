/**
 * Check if the WC app is running on iOS.
 *
 * @return {boolean} Whether the WC app is running on iOS.
 */
const isWCIos = () => {
	return window.navigator.userAgent.toLowerCase().includes( 'wc-ios' );
};

/**
 * Check if the WC app is running on Android.
 *
 * @return {boolean} Whether the WC app is running on Android.
 */
const isWCAndroid = () => {
	return window.navigator.userAgent.toLowerCase().includes( 'wc-android' );
};

export { isWCIos, isWCAndroid };
