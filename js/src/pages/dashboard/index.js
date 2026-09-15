/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { getNewPath, getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import DifferentCurrencyNotice from '~/components/different-currency-notice';
import MainTabNav from '~/components/main-tab-nav';
import CustomerEffortScorePrompt from '~/components/customer-effort-score-prompt';
import AppDateRangeFilterPicker from './app-date-range-filter-picker';
import SummarySection from './summary-section';
import CampaignCreationSuccessGuide from './campaign-creation-success-guide';
import AllProgramsTableCard from './all-programs-table-card';
import { glaData, GUIDE_NAMES } from '~/constants';
import { subpaths, getCreateCampaignUrl } from '~/utils/urls';
import isWCTracksEnabled from '~/utils/isWCTracksEnabled';
import EditPaidAdsCampaign from '~/pages/edit-paid-ads-campaign';
import CreatePaidAdsCampaign from '~/pages/create-paid-ads-campaign';
import { CTA_CREATE_ANOTHER_CAMPAIGN, CTA_CONFIRM } from './constants';
import { recordGlaEvent } from '~/utils/tracks';
import RebrandingTour from '~/components/tours/rebranding-tour';
import PMaxImproveAssetsBanner from '~/components/pmax-improve-assets-banner';
import UnclaimedIncentiveNotice from '~/components/unclaimed-incentive-notice';
import ExperienceRatingBanner from '~/components/experience-rating-banner';
import RaiseBudgetRecommendationBanner from '~/components/raise-budget-recommendation-banner';
import YouTubeShoppingTour from '~/components/tours/youtube-shopping-tour';
import SubmissionSuccessGuide from '~/pages/product-feed/submission-success-guide';
import EuPoliticalDeclaration from '~/components/eu-political-declaration';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import EuPoliticalDeclarationProvider from '~/components/eu-political-declaration/eu-political-declaration-provider';
import './index.scss';

/**
 * @fires gla_modal_closed when CES modal is closed.
 */
const Dashboard = () => {
	const [ isCESPromptOpen, setCESPromptOpen ] = useState( false );
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	const handleCampaignCreationSuccessGuideClose = useCallback(
		( e, specifiedAction ) => {
			const action = specifiedAction || e.currentTarget.dataset.action;
			const nextQuery = {
				...getQuery(),
				guide: undefined,
			};
			getHistory().replace( getNewPath( nextQuery ) );

			if ( action === CTA_CREATE_ANOTHER_CAMPAIGN ) {
				getHistory().push( getCreateCampaignUrl() );
			} else if ( action === CTA_CONFIRM ) {
				setCESPromptOpen( true );
			}

			recordGlaEvent( 'gla_modal_closed', {
				context: GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS,
				action,
			} );
		},
		[ setCESPromptOpen ]
	);

	const query = getQuery();
	switch ( query.subpath ) {
		case subpaths.editCampaign:
			return (
				<EuPoliticalDeclarationProvider context="edit-ads">
					<EditPaidAdsCampaign />
				</EuPoliticalDeclarationProvider>
			);
		case subpaths.createCampaign:
			return (
				<EuPoliticalDeclarationProvider context="create-ads">
					<CreatePaidAdsCampaign />
				</EuPoliticalDeclarationProvider>
			);
	}

	const trackEventReportId = 'dashboard';
	const { enableReports } = glaData;

	const ReportsLink = () => {
		return (
			<Link href={ getNewPath( null, '/google/reports' ) }>
				<AppButton isPrimary>
					{ __( 'View Reports', 'google-listings-and-ads' ) }
				</AppButton>
			</Link>
		);
	};

	const isCampaignCreationSuccessGuideOpen =
		query?.guide === GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS;
	const isSubmissionSuccessOpen =
		query?.guide === GUIDE_NAMES.SUBMISSION_SUCCESS;
	const wcTracksEnabled = isWCTracksEnabled();

	return (
		<>
			<div className="gla-dashboard">
				<UnclaimedIncentiveNotice />
				<PMaxImproveAssetsBanner />
				<ExperienceRatingBanner />
				<DifferentCurrencyNotice context="dashboard" />
				<MainTabNav />
				<RaiseBudgetRecommendationBanner />
				<RebrandingTour />
				{ hasGoogleMCConnection && <YouTubeShoppingTour /> }
				<div className="gla-dashboard__filter">
					<AppDateRangeFilterPicker
						trackEventReportId={ trackEventReportId }
					/>
					{ enableReports && hasGoogleMCConnection && (
						<ReportsLink />
					) }
				</div>
				<div className="gla-dashboard__performance">
					<SummarySection />
				</div>

				{ /* Wrapping AllProgramsTableCard with
				EuPoliticalDeclarationProvider to enable the EU political
				declaration modal to be triggered from within the programs
				table, if necessary when enabling/disabling campaigns. */ }
				<EuPoliticalDeclarationProvider context="dashboard">
					<div className="gla-dashboard__programs">
						<AllProgramsTableCard
							trackEventReportId={ trackEventReportId }
						/>
					</div>

					<EuPoliticalDeclaration />
				</EuPoliticalDeclarationProvider>
			</div>

			{ isCampaignCreationSuccessGuideOpen && (
				<CampaignCreationSuccessGuide
					onGuideRequestClose={
						handleCampaignCreationSuccessGuideClose
					}
				/>
			) }
			{ isSubmissionSuccessOpen && <SubmissionSuccessGuide /> }
			{ isCESPromptOpen && wcTracksEnabled && (
				<CustomerEffortScorePrompt
					label={ __(
						'How easy was it to create a Google Ad campaign?',
						'google-listings-and-ads'
					) }
					secondLabel={ __(
						'How easy was it to understand the requirements for the Google Ad campaign creation?',
						'google-listings-and-ads'
					) }
					eventContext={ GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS }
				/>
			) }
		</>
	);
};

export default Dashboard;
