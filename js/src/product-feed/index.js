/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import NavigationClassic from '.~/components/navigation-classic';
import IssuesTableCard from './issues-table-card';
import ProductFeedTableCard from './product-feed-table-card';
import SubmissionSuccessGuide from './submission-success-guide';
import CustomerEffortScorePrompt from '.~/components/customer-effort-score-prompt';
import ProductStatistics from './product-statistics';
import './index.scss';
import { GUIDE_NAMES, LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';
import isWCTracksEnabled from '.~/utils/isWCTracksEnabled';

const ProductFeed = () => {
	const [ canCESPromptOpen, setCESPromptOpen ] = useState( false );

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
			<NavigationClassic />
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
				<ProductStatistics />
				<IssuesTableCard />
				<ProductFeedTableCard trackEventReportId="product-feed" />
			</div>
		</>
	);
};

export default ProductFeed;
