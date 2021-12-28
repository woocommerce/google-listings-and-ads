/**
 * Checks whether the WC Tracks is enabled.
 *
 * @return {boolean} True / false.
 */
const isWCTracksEnabled = () => {
	return !! window.wcTracks?.isEnabled;
};

export default isWCTracksEnabled;
