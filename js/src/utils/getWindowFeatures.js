/**
 * Returns a string of window.open()'s features that aligns with the center of the current window.
 *
 * @param {Window} defaultView The window object.
 * @param {number} windowWidth Expected window width.
 * @param {number} windowHeight Expected window height.
 * @return {string} Centered alignment window features for calling with window.open().
 */
const getWindowFeatures = ( defaultView, windowWidth, windowHeight ) => {
	const { innerWidth, innerHeight, screenX, screenY, screen } = defaultView;
	const width = Math.min( windowWidth, screen.availWidth );
	const height = Math.min( windowHeight, screen.availHeight );
	const left = ( innerWidth - width ) / 2 + screenX;
	const top = ( innerHeight - height ) / 2 + screenY;

	return `popup=1,left=${ left },top=${ top },width=${ width },height=${ height }`;
};

export default getWindowFeatures;
