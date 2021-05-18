/**
 * Checks whether the WC Navigation feature is enabled.
 *
 * @return {boolean} True / false.
 */
const isWCNavigationEnabled = () => {
	return window.wcAdminFeatures?.navigation;
};

export default isWCNavigationEnabled;
