/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';

const useAdsCampaigns = () => {
	return useSelect( ( select ) => {
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
