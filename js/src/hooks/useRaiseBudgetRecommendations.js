/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { RAISE_CAMPAIGN_BUDGET } from '~/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';

/**
 * @typedef {Object} CampaignRecommendation
 * @property {number} id The unique identifier for the recommendation.
 * @property {string} type The type of recommendation.
 * @property {string} resource_name The resource name for the recommendation.
 * @property {number} campaign_id The ID of the campaign.
 * @property {string} campaign_name The name of the campaign.
 * @property {string} campaign_status The status of the campaign.
 * @property {string} last_synced The last synced date - ISO date string
 */

/**
 * Retrieves campaigns with budget recommendations to raise.
 *
 * @return {Object} An object containing:
 *   - {Array<CampaignRecommendation>} campaigns: An array of campaigns with budget recommendations, or an empty array if none.
 *   - {boolean} hasFinishedResolution: Whether the recommendations resolution has completed.
 */
const useRaiseBudgetRecommendations = () => {
	const { hasGoogleAdsConnection, hasFinishedResolution } =
		useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( ! hasGoogleAdsConnection ) {
				return {
					campaigns: [],
					hasFinishedResolution,
				};
			}

			const selector = select( STORE_KEY );
			const campaigns = selector.getAdsRecommendations(
				RAISE_CAMPAIGN_BUDGET
			);
			const hasResolvedRecommendations = selector.hasFinishedResolution(
				'getAdsRecommendations',
				[ RAISE_CAMPAIGN_BUDGET ]
			);

			if ( ! campaigns?.length ) {
				return {
					campaigns: [],
					hasFinishedResolution: hasResolvedRecommendations,
				};
			}

			return {
				campaigns,
				hasFinishedResolution: true,
			};
		},
		[ hasFinishedResolution, hasGoogleAdsConnection ]
	);
};

export default useRaiseBudgetRecommendations;
