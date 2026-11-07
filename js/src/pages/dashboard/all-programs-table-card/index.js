/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { Card, CardHeader } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import AddPaidCampaignButton from '~/components/paid-ads/add-paid-campaign-button';
import AppNotice from '~/components/app-notice';
import AppSpinner from '~/components/app-spinner';
import Text from '~/components/app-text';
import { recordGlaEvent } from '~/utils/tracks';
import ProgramsDataViews from './programs-data-views';
import './index.scss';

/**
 * When there are campaigns with budget recommendations in the table.
 *
 * @event gla_raise_budget_recommendation_badge_campaigns
 * @property {string} context The context in which the banner was dismissed. Set to 'programs-table-card'.
 * @property {Array<number>} campaign_ids The IDs of the campaigns with budget recommendations.
 */

/**
 * All programs table.
 *
 * @fires gla_raise_budget_recommendation_badge_campaigns when there are campaigns with budget recommendations in the table.
 * @param {Object} props React props.
 * @param {string} [props.trackEventReportId] Report ID used in the table tracking events.
 */
const AllProgramsTableCard = ( { trackEventReportId } ) => {
	const dataViewsStatus = useDataViewsScript();
	const { data: adsCampaignsData } = useAdsCampaigns();
	const { campaigns: raiseBudgetRecommendationCampaigns } =
		useRaiseBudgetRecommendations();

	useEffect( () => {
		if (
			! adsCampaignsData?.length ||
			! raiseBudgetRecommendationCampaigns?.length
		) {
			return;
		}

		const raiseBudgetRecommendationCampaignIds = new Set(
			raiseBudgetRecommendationCampaigns.map( ( obj ) => obj.campaign_id )
		);
		const existingRecommendedCampaignIds = adsCampaignsData
			.filter( ( campaignData ) =>
				raiseBudgetRecommendationCampaignIds.has( campaignData.id )
			)
			.map( ( campaignData ) => campaignData.id );

		if ( existingRecommendedCampaignIds.length ) {
			recordGlaEvent( 'gla_raise_budget_recommendation_badge_campaigns', {
				context: 'programs-table-card',
				campaign_ids: existingRecommendedCampaignIds,
			} );
		}
	}, [ adsCampaignsData, raiseBudgetRecommendationCampaigns ] );

	return (
		<Card className="gla-all-programs-table-card">
			<CardHeader>
				<Text variant="title-small" as="h2">
					{ __( 'Programs', 'google-listings-and-ads' ) }
				</Text>
				<AddPaidCampaignButton
					eventProps={ { context: 'programs-table-card' } }
				/>
			</CardHeader>

			{ dataViewsStatus === 'loading' && <AppSpinner /> }

			{ dataViewsStatus === 'failed' && (
				<AppNotice status="warning" isDismissible={ false }>
					{ __(
						'There was an error loading the programs.',
						'google-listings-and-ads'
					) }
				</AppNotice>
			) }

			{ dataViewsStatus === 'ready' && (
				<ProgramsDataViews trackEventReportId={ trackEventReportId } />
			) }
		</Card>
	);
};

export default AllProgramsTableCard;
