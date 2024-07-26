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
import AppButton from '.~/components/app-button';
import DifferentCurrencyNotice from '.~/components/different-currency-notice';
import NavigationClassic from '.~/components/navigation-classic';
import CustomerEffortScorePrompt from '.~/components/customer-effort-score-prompt';
import AppDateRangeFilterPicker from './app-date-range-filter-picker';
import SummarySection from './summary-section';
import CampaignCreationSuccessGuide from './campaign-creation-success-guide';
import AllProgramsTableCard from './all-programs-table-card';
import { glaData, GUIDE_NAMES } from '.~/constants';
import { subpaths, getCreateCampaignUrl } from '.~/utils/urls';
import isWCTracksEnabled from '.~/utils/isWCTracksEnabled';
import EditFreeCampaign from '.~/edit-free-campaign';
import EditPaidAdsCampaign from '.~/pages/edit-paid-ads-campaign';
import CreatePaidAdsCampaign from '.~/pages/create-paid-ads-campaign';
import { CTA_CREATE_ANOTHER_CAMPAIGN, CTA_CONFIRM } from './constants';
import { recordGlaEvent } from '.~/utils/tracks';
import RebrandingTour from '.~/components/tours/rebranding-tour';
import './index.scss';

/**
 * @fires gla_modal_closed when CES modal is closed.
 */
const Dashboard = () => {
	const [ isCESPromptOpen, setCESPromptOpen ] = useState( false );

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
		case subpaths.editFreeListings:
			return <EditFreeCampaign />;
		case subpaths.editCampaign:
			return <EditPaidAdsCampaign />;
		case subpaths.createCampaign:
			return <CreatePaidAdsCampaign />;
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
	const wcTracksEnabled = isWCTracksEnabled();

	return (
		<>
			<div className="gla-dashboard">
				<DifferentCurrencyNotice context="dashboard" />
				<NavigationClassic />
				<RebrandingTour />
				<div className="gla-dashboard__filter">
					<AppDateRangeFilterPicker
						trackEventReportId={ trackEventReportId }
					/>
					{ enableReports && <ReportsLink /> }
				</div>
				<div className="gla-dashboard__performance">
					<SummarySection />
				</div>
				<div className="gla-dashboard__programs">
					<AllProgramsTableCard
						trackEventReportId={ trackEventReportId }
					/>
				</div>
			</div>
			{ isCampaignCreationSuccessGuideOpen && (
				<CampaignCreationSuccessGuide
					onGuideRequestClose={
						handleCampaignCreationSuccessGuideClose
					}
				/>
			) }
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
