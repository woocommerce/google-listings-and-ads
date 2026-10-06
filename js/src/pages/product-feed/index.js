/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import IssuesTableCard from './issues-table-card';
import ProductFeedTableCard from './product-feed-table-card';
import SubmissionSuccessGuide from './submission-success-guide';
import CustomerEffortScorePrompt from '~/components/customer-effort-score-prompt';
import ProductOverview from './overview';
import TechnicalDetails, {
	TECHNICAL_DETAILS_ID,
} from './sync-health/technical-details';
import CreateCampaignNotice from './product-statistics/create-campaign-notice';
import './index.scss';
import { GUIDE_NAMES, LOCAL_STORAGE_KEYS } from '~/constants';
import localStorage from '~/utils/localStorage';
import isWCTracksEnabled from '~/utils/isWCTracksEnabled';
import ExperienceRatingBanner from '~/components/experience-rating-banner';

const ProductFeed = () => {
	const [ canCESPromptOpen, setCESPromptOpen ] = useState( false );
	const [ journey, setJourney ] = useState( '' );
	const [ technicalOpen, setTechnicalOpen ] = useState( false );
	const tableRef = useRef();

	// Picking a segment filters the product table and brings it into view.
	const handleJourneyChange = ( segment ) => {
		setJourney( segment );
		if ( segment ) {
			tableRef.current?.scrollIntoView?.( { behavior: 'smooth' } );
		}
	};

	const showTechnicalDetails = () => {
		setTechnicalOpen( true );
		document
			.getElementById( TECHNICAL_DETAILS_ID )
			?.scrollIntoView?.( { behavior: 'smooth' } );
	};

	// Show submission success guide modal by visiting the path with a specific query `guide=submission-success`.
	// For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
	const isSubmissionSuccessOpen =
		getQuery()?.guide === GUIDE_NAMES.SUBMISSION_SUCCESS;

	const wcTracksEnabled = isWCTracksEnabled();

	useEffect( () => {
		if ( ! canCESPromptOpen ) {
			const canCESPromptOpenLocal = localStorage.get(
				LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
			);

			const canOpen =
				! isSubmissionSuccessOpen &&
				canCESPromptOpenLocal &&
				wcTracksEnabled;

			setCESPromptOpen( canOpen );
		}
	}, [ isSubmissionSuccessOpen, canCESPromptOpen, wcTracksEnabled ] );

	return (
		<>
			<ExperienceRatingBanner />
			<MainTabNav />
			{ isSubmissionSuccessOpen && <SubmissionSuccessGuide /> }
			{ canCESPromptOpen && (
				<CustomerEffortScorePrompt
					label={ __(
						'How easy was it to set up Google for WooCommerce?',
						'google-listings-and-ads'
					) }
					secondLabel={ __(
						'How easy was it to understand the requirements for the Google for WooCommerce setup?',
						'google-listings-and-ads'
					) }
					eventContext={ GUIDE_NAMES.SUBMISSION_SUCCESS }
				/>
			) }
			<div className="gla-product-feed">
				<ProductOverview
					journey={ journey }
					onJourneyChange={ handleJourneyChange }
					onShowTechnicalDetails={ showTechnicalDetails }
				/>
				<CreateCampaignNotice />
				<IssuesTableCard />
				<div ref={ tableRef }>
					<ProductFeedTableCard
						trackEventReportId="product-feed"
						journey={ journey }
					/>
				</div>
				<TechnicalDetails
					open={ technicalOpen }
					onOpenChange={ setTechnicalOpen }
				/>
			</div>
		</>
	);
};

export default ProductFeed;
