/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { ENHANCED_CONVERSION_TERMS_BASE_URL } from '.~/constants';

const useGoogleAdsEnhancedConversionTermsURL = () => {
	return useSelect( ( select ) => {
		const adsAccount = select( STORE_KEY ).getGoogleAdsAccount();

		const url = addQueryArgs( ENHANCED_CONVERSION_TERMS_BASE_URL, {
			ocid: adsAccount?.ocid || 0,
			eppn: 'enhancedconversionsaccountlevelsettings',
		} );

		return { url };
	}, [] );
};

export default useGoogleAdsEnhancedConversionTermsURL;
