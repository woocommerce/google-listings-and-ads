/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import { glaData } from '.~/constants';

const useAdsCampaigns = () => {
	return useSelect( ( select ) => {
		// TODO: ideally adsSetupComplete should be retrieved from API endpoint
		// and then put into wp-data.
		// With that in place, then we don't need to depend on glaData
		// which requires force reload using window.location.href.
		const { adsSetupComplete } = glaData;
		if ( ! adsSetupComplete ) {
			return {
				loading: false,
				data: [],
			};
		}

		const { getAdsCampaigns, isResolving } = select( STORE_KEY );

		const data = getAdsCampaigns();
		const loading = isResolving( 'getAdsCampaigns' );

		return {
			loading,
			data,
		};
	} );
};

export default useAdsCampaigns;
