/**
 * Internal dependencies
 */
import { ENHANCED_ADS_TOS_BASE_URL } from '.~/constants';

const useOpenTermsURL = () => {
	// Extracted as hook for later we want to get the deep link
	// @todo: Deeplink
	const openTermsURL = () => {
		window.open( ENHANCED_ADS_TOS_BASE_URL, '_blank' );
	};

	return { openTermsURL };
};

export default useOpenTermsURL;
