/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH } from '~/constants';

/**
 * Retrieves the Performance Max (PMax) campaign with the highest spending
 *
 * @return {Object} An object containing:
 *   - {Object|null} campaign: The highest spending enabled PMax campaign with a recommendation, or null if none.
 *   - {boolean} hasFinishedResolution: Whether the recommendations resolution has completed.
 */
const useRecommendedPMaxCampaign = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const recommendations = selector.getAdsRecommendations( [
			PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH,
		] );
		const hasResolvedRecommendations = selector.hasFinishedResolution(
			'getAdsRecommendations',
			[ [ PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH ] ]
		);

		if ( ! recommendations?.length ) {
			return {
				campaign: null,
				hasFinishedResolution: hasResolvedRecommendations,
			};
		}

		return {
			campaign: recommendations[ 0 ],
			hasFinishedResolution: true,
		};
	}, [] );
};

export default useRecommendedPMaxCampaign;
