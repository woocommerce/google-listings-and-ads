/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import {
	CAMPAIGN_TYPE_PMAX,
	PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH,
} from '~/constants';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';

/**
 * Retrieves the Performance Max (PMax) campaign with the highest spending
 * that is currently enabled, and check if there are asset optimization recommendations for it.
 *
 * @return {Object} An object containing:
 *   - {Object|null} campaign: The highest spending enabled PMax campaign with a recommendation, or null if none.
 *   - {boolean} hasFinishedResolution: Whether the recommendations resolution has completed.
 */
const useRecommendedPMaxCampaign = () => {
	const { data: adsCampaignsData, loaded } = useAdsCampaigns();

	const { highestAmountCampaign } = useMemo( () => {
		const pmaxCampaigns = ( adsCampaignsData || [] ).filter(
			( { type, status } ) =>
				type === CAMPAIGN_TYPE_PMAX && status === 'enabled'
		);

		if ( ! pmaxCampaigns.length ) {
			return {
				highestAmountCampaign: null,
			};
		}

		const filteredHighestAmountCampaign = pmaxCampaigns.reduce(
			( max, campaign ) =>
				( campaign.amount ?? 0 ) > ( max.amount ?? 0 ) ? campaign : max,
			pmaxCampaigns[ 0 ]
		);

		return {
			highestAmountCampaign: filteredHighestAmountCampaign,
		};
	}, [ adsCampaignsData ] );

	return useSelect(
		( select ) => {
			if ( ! highestAmountCampaign || ! loaded ) {
				return {
					campaign: null,
					hasFinishedResolution: loaded,
				};
			}

			const selector = select( STORE_KEY );
			const recommendations = selector.getAdsRecommendations(
				PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH
			);
			const hasResolvedRecommendations = selector.hasFinishedResolution(
				'getAdsRecommendations',
				[ PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH ]
			);

			if ( ! hasResolvedRecommendations && recommendations === null ) {
				return {
					campaign: null,
					hasFinishedResolution: false,
				};
			}

			if ( ! recommendations.length ) {
				return {
					campaign: null,
					hasFinishedResolution: true,
				};
			}

			const { id } = highestAmountCampaign;

			const hasHighestSpendingCampaignRecommendation =
				recommendations.some(
					( recommendation ) => recommendation.campaign_id === id
				);

			if ( ! hasHighestSpendingCampaignRecommendation ) {
				return {
					campaign: null,
					hasFinishedResolution: true,
				};
			}

			return {
				campaign: highestAmountCampaign,
				hasFinishedResolution: true,
			};
		},
		[ highestAmountCampaign, loaded ]
	);
};

export default useRecommendedPMaxCampaign;
