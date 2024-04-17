/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { GUIDE_NAMES, LOCAL_STORAGE_KEYS } from '.~/constants';
import { getProductFeedUrl } from '.~/utils/urls';
import { recordGlaEvent } from '.~/utils/tracks';
import { GLA_MODAL_CLOSED_EVENT_NAME } from './constants';
import Guide from '.~/external-components/wordpress/guide';
import localStorage from '.~/utils/localStorage';
import DynamicScreenContent from './dynamic-screen-content';
import DynamicScreenActions from './dynamic-screen-actions';
import SetupSuccess from './setup-success';
import wooLogoURL from './woocommerce-logo.svg';
import googleLogoURL from '.~/images/google-logo.svg';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import './index.scss';

const image = (
	<div className="gla-submission-success-guide__logo-block">
		<div className="gla-submission-success-guide__logo-item">
			<img
				src={ wooLogoURL }
				alt={ __( 'WooCommerce Logo', 'google-listings-and-ads' ) }
				width="145"
				height="31"
			/>
		</div>
		<div className="gla-submission-success-guide__logo-separator-line" />
		<div className="gla-submission-success-guide__logo-item">
			<img
				src={ googleLogoURL }
				alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
				width="106"
				height="36"
			/>
		</div>
	</div>
);

const handleGuideFinish = ( e ) => {
	getHistory().replace( getProductFeedUrl() );

	// Since there is no built-in way to distinguish the modal/guide is closed by what action,
	// here is a workaround by identifying the close button's data-action attribute.
	let action = 'dismiss';

	if ( e ) {
		const target = e.currentTarget || e.target;
		action = target.dataset.action || action;
	}
	recordGlaEvent( GLA_MODAL_CLOSED_EVENT_NAME, {
		context: GUIDE_NAMES.SUBMISSION_SUCCESS,
		action,
	} );
};

// There will always be at least 2 pages because we will require a connected Ads account during onboarding.
const pages = [
	{
		image,
		content: <SetupSuccess />,
	},
	{
		image,
		content: <DynamicScreenContent />,
		actions: <DynamicScreenActions onModalClose={ handleGuideFinish } />,
	},
];

/**
 * Modal window to greet the user at Product Feed, after successful completion of onboarding.
 *
 * Show this guide modal by visiting the path with a specific query `guide=submission-success`.
 * For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
 *
 * @fires gla_modal_closed with `action: 'create-paid-campaign' | 'maybe-later' | 'close' | 'dismiss'`
 * @fires gla_modal_open with `context: GUIDE_NAMES.SUBMISSION_SUCCESS`
 */
const SubmissionSuccessGuide = () => {
	useEffect( () => {
		recordGlaEvent( 'gla_modal_open', {
			context: GUIDE_NAMES.SUBMISSION_SUCCESS,
		} );

		// Set a flag in local storage to indicate the CES prompt can be shown
		// when the user enters product feed for the first time after setting up.
		localStorage.set(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN,
			true
		);
	}, [] );

	// Side effects to try to get the data we need for the modals as soon as possible.
	useAdsCampaigns();
	useAcceptedCustomerDataTerms();
	useAllowEnhancedConversions();

	return (
		<Guide
			className="gla-submission-success-guide"
			backButtonText={ __( 'Back', 'google-listings-and-ads' ) }
			pages={ pages }
			onFinish={ handleGuideFinish }
		/>
	);
};

export default SubmissionSuccessGuide;
