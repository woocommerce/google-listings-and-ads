/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Notice, Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getEditCampaignUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import DeltaValue from '~/components/delta-value';
import useBudgetMetrics from '~/hooks/useBudgetMetrics';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import Badge from '~/components/badge';
import AppButton from '~/components/app-button';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import formatNumber from '~/utils/formatNumber';
import './index.scss';

const RAISE_BUDGET_RECOMMENDATION_BANNER_CONTEXT =
	'raise_budget_recommendation_banner';

/**
 * When the banner is shown.
 *
 * @event gla_raise_budget_recommendation_banner_shown
 * @property {string} context The context in which the banner is shown. Set to 'raise_budget_recommendation_banner'.
 */

/**
 * When the "View recommendation" button is clicked.
 *
 * @event gla_raise_budget_recommendation_banner_view_recommendation_clicked
 * @property {string} context The context in which the banner is shown. Set to 'raise_budget_recommendation_banner'.
 * @property {number} campaign_id The ID of the campaign for which the recommendation is being viewed.
 */

/**
 * When the banner is dismissed by clicking the "Dismiss" button or the close icon.
 *
 * @event gla_raise_budget_recommendation_dismiss_clicked
 * @property {string} context The context in which the banner was dismissed. Set to 'raise_budget_recommendation_banner'.
 * @property {number} campaign_id The ID of the campaign for which the banner was dismissed.
 */

/**
 * Displays a dismissible banner prompting users to raise the budget of a campaign.
 *
 * The banner is shown only if:
 * - There are enabled campaigns.
 * - There are relevant budget increase recommendations.
 *
 * When dismissed, the banner will not reappear until the expiry time elapses.
 * Clicking "View recommendation" navigates to the recommendation details page for the campaign.
 *
 * @fires gla_raise_budget_recommendation_banner_shown when the banner is displayed.
 * @fires gla_raise_budget_recommendation_banner_view_recommendation_clicked when the "View recommendation" button is clicked.
 * @fires gla_raise_budget_recommendation_dismiss_clicked when the banner is dismissed.
 *
 * @param {Object} props Component properties.
 * @param {Function} props.onBannerDismissed Callback function to call when the banner is dismissed.
 * @return {JSX.Element|null} The banner component, or null if not applicable.
 */
const Banner = ( { onBannerDismissed } ) => {
	const { campaigns: recommendedCampaigns } = useRaiseBudgetRecommendations();
	const { data: allCampaigns } = useAdsCampaigns();
	// Order recommendations by current budget amount in descending order.
	const orderedRecommendedCampaigns = recommendedCampaigns.sort(
		( a, b ) =>
			b.details.campaign_budget_recommendation.current_budget_amount -
			a.details.campaign_budget_recommendation.current_budget_amount
	);
	const recommendedCampaign = orderedRecommendedCampaigns?.[ 0 ] || {};
	const { campaign_id, campaign_name } = recommendedCampaign;
	const campaign = allCampaigns?.find( ( el ) => el.id === campaign_id );
	const { data: budgetMetricsData } = useBudgetMetrics(
		campaign?.targeted_locations,
		campaign?.amount
	);

	useEffect( () => {
		if ( campaign && budgetMetricsData ) {
			recordGlaEvent( 'gla_raise_budget_recommendation_banner_shown', {
				context: RAISE_BUDGET_RECOMMENDATION_BANNER_CONTEXT,
			} );
		}
	}, [ campaign, budgetMetricsData ] );

	if ( ! campaign || ! budgetMetricsData ) {
		return null;
	}

	const handleOnViewRecommendation = () => {
		onBannerDismissed();

		recordGlaEvent(
			'gla_raise_budget_recommendation_banner_view_recommendation_clicked',
			{
				context: RAISE_BUDGET_RECOMMENDATION_BANNER_CONTEXT,
				campaign_id,
			}
		);

		const editCampaignUrl = getEditCampaignUrl(
			campaign_id,
			'asset-group'
		);
		getHistory().push( editCampaignUrl );
	};

	const handleDismiss = () => {
		onBannerDismissed();

		recordGlaEvent(
			'gla_raise_budget_recommendation_banner_dismiss_clicked',
			{
				context: RAISE_BUDGET_RECOMMENDATION_BANNER_CONTEXT,
				campaign_id,
			}
		);
	};

	const recommendedCampaignMetrics =
		recommendedCampaign?.details?.campaign_budget_recommendation?.budget_options?.find(
			( { level } ) => level.toLowerCase() === 'recommended'
		)?.metrics;
	const percentageIncrease = Math.round(
		( ( recommendedCampaignMetrics.conversions -
			budgetMetricsData.metrics.conversions ) /
			budgetMetricsData.metrics.conversions ) *
			100
	);
	const conversionValueIncrease = formatNumber(
		recommendedCampaignMetrics.conversions_value -
			budgetMetricsData.metrics.conversionsValue
	);

	return (
		<Notice
			className="gla-raise-budget-recommendation-banner"
			isDismissible={ true }
			onRemove={ handleDismiss }
		>
			{ percentageIncrease && (
				<header className="gla-raise-budget-recommendation-banner__header">
					<Badge intent="info">
						<DeltaValue amount={ percentageIncrease } suffix="%" />
					</Badge>
				</header>
			) }

			<Flex
				className="gla-raise-budget-recommendation-banner__body"
				direction={ [ 'column', 'row' ] }
				gap={ 6 }
				align="stretch"
			>
				<FlexBlock>
					<p className="gla-raise-budget-recommendation-banner__title">
						{ sprintf(
							// translators: %s: The campaign name with with budget recommendation.
							__(
								'You missed conversion value in “%s” campaign because you’re limited by budget. Increasing your budget can result in more conversion value.',
								'google-listings-and-ads'
							),
							campaign_name
						) }
					</p>
					<p>
						{ __(
							'Recommended because you missed out potential traffic last week based on data from the ad auctions you participated in.',
							'google-listings-and-ads'
						) }
					</p>
				</FlexBlock>

				{ conversionValueIncrease && (
					<FlexItem className="gla-raise-budget-recommendation-banner__estimates">
						<p>
							{ __(
								'Projected weekly estimates',
								'google-listings-and-ads'
							) }
						</p>

						<p className="gla-raise-budget-recommendation-banner__estimates-value">
							<span>+{ conversionValueIncrease }</span>

							<span>
								{ __(
									'Conversion value',
									'google-listings-and-ads'
								) }
							</span>
						</p>
					</FlexItem>
				) }
			</Flex>

			<div className="gla-raise-budget-recommendation-banner__actions">
				<AppButton onClick={ handleOnViewRecommendation } isSecondary>
					{ __( 'View recommendation', 'google-listings-and-ads' ) }
				</AppButton>

				<AppButton isTertiary onClick={ handleDismiss }>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</div>
		</Notice>
	);
};

export default Banner;
