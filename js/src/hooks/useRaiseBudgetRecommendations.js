/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { CAMPAIGN_BUDGET, MARGINAL_ROI_CAMPAIGN_BUDGET } from '~/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';

/**
 * @typedef {Object} BudgetMetrics
 * @property {string|number} cost The cost amount.
 * @property {number} conversions The number of conversions.
 * @property {number} conversions_value The value of conversions.
 */

/**
 * @typedef {Object} BudgetOption
 * @property {BudgetMetrics} metrics Metrics associated with this budget option.
 * @property {string|number} budget_amount The budget amount.
 * @property {"low"|"recommended"|"high"} level The budget level.
 */

/**
 * @typedef {Object} CampaignBudgetRecommendation
 * @property {number} current_budget_amount The current budget amount.
 * @property {number} recommended_budget_amount The recommended budget amount.
 * @property {BudgetOption[]} budget_options The budget options.
 */

/**
 * @typedef {Object} Details
 * @property {CampaignBudgetRecommendation} campaign_budget_recommendation The campaign budget recommendation details.
 */

/**
 * @typedef {Object} CampaignRecommendation
 * @property {number} id The unique identifier for the recommendation.
 * @property {string} type The type of recommendation.
 * @property {string} resource_name The resource name for the recommendation.
 * @property {number} campaign_id The ID of the campaign.
 * @property {string} campaign_name The name of the campaign.
 * @property {string} campaign_status The status of the campaign.
 * @property {number} customer_id The customer ID.
 * @property {Details} details The current budget details.
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
			const campaigns = selector.getAdsRecommendations( [
				CAMPAIGN_BUDGET,
				MARGINAL_ROI_CAMPAIGN_BUDGET,
			] );
			const hasResolvedRecommendations = selector.hasFinishedResolution(
				'getAdsRecommendations',
				[ CAMPAIGN_BUDGET, MARGINAL_ROI_CAMPAIGN_BUDGET ]
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
