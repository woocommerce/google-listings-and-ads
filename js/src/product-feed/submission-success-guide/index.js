/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useCallback } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData, GUIDE_NAMES, LOCAL_STORAGE_KEYS } from '.~/constants';
import { getProductFeedUrl } from '.~/utils/urls';
import { recordGlaEvent } from '.~/utils/tracks';
import { GLA_MODAL_CLOSED_EVENT_NAME } from './constants';
import Guide from '.~/external-components/wordpress/guide';
import localStorage from '.~/utils/localStorage';
import DynamicScreenContent from './dynamic-screen-content';
import DynamicScreenFooter from './dynamic-screen-footer';
import SetupSuccess from './setup-success';
import SetupSuccessFooter from './setup-success/footer';
import wooLogoURL from './woocommerce-logo.svg';
import googleLogoURL from '.~/images/google-logo.svg';
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
	// here is a workaround by identifying the close button's data-aciton attribute.
	const target = e.currentTarget || e.target;
	const action = target.dataset.action || 'dismiss';
	recordGlaEvent( GLA_MODAL_CLOSED_EVENT_NAME, {
		context: GUIDE_NAMES.SUBMISSION_SUCCESS,
		action,
	} );
};

const pages = [
	{
		image,
		content: <SetupSuccess />,
		footer: <SetupSuccessFooter onClose={ handleGuideFinish } />,
	},
	{
		image,
		content: <DynamicScreenContent />,
		footer: <DynamicScreenFooter onClose={ handleGuideFinish } />,
	},
];

if ( ! glaData.adsConnected ) {
	pages.pop();
}

/**
 * Modal window to greet the user at Product Feed, after successful completion of onboarding.
 *
 * Show this guide modal by visiting the path with a specific query `guide=submission-success`.
 * For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
 *
 * @fires gla_modal_closed with `action: 'create-paid-campaign' | 'maybe-later' | 'view-product-feed' | 'dismiss'`
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

	// @todo: Review whether we need that function since we have moved the buttons to be per page now.
	const renderFinish = useCallback( () => null, [] );

	return (
		<Guide
			className="gla-submission-success-guide"
			backButtonText={ __( 'Back', 'google-listings-and-ads' ) }
			pages={ pages }
			renderFinish={ renderFinish }
			onFinish={ handleGuideFinish }
		/>
	);
};

export default SubmissionSuccessGuide;
