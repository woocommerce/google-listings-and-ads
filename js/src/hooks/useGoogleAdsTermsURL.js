/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

export const ENHANCED_ADS_TERMS_BASE_URL =
	'https://ads.google.com/aw/conversions/customersettings';

const useGoogleAdsEnhancedConversionTermsURL = () => {
	return useSelect( ( select ) => {
		const adsAccount = select( STORE_KEY ).getGoogleAdsAccount();
		const hasFinishedResolution = select( STORE_KEY ).hasFinishedResolution(
			'getGoogleAdsAccount'
		);

		const url = addQueryArgs( ENHANCED_ADS_TERMS_BASE_URL, {
			ocid: adsAccount?.ocid,
			eppn: 'customerDataTerms',
		} );

		return { url, hasFinishedResolution };
	}, [] );
};

export default useGoogleAdsEnhancedConversionTermsURL;
