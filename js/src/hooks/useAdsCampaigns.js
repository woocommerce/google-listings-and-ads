/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import { glaData } from '.~/constants';

const selectorName = 'getAdsCampaigns';

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
				loaded: true,
				data: [],
			};
		}

		const selector = select( STORE_KEY );
		const data = selector[ selectorName ]();
		const loading = selector.isResolving( selectorName );
		const loaded = selector.hasFinishedResolution( selectorName );

		return {
			loading,
			loaded,
			data,
		};
	}, [] );
};

export default useAdsCampaigns;
