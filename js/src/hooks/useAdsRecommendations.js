/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getAdsRecommendations';

const useAdsRecommendations = ( type ) => {
	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );

			return {
				recommendations: selector[ selectorName ]( type ),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ type ]
				),
			};
		},
		[ type ]
	);
};

export default useAdsRecommendations;
