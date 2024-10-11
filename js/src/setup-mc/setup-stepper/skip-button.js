/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import AppButton from '.~/components/app-button';
import SkipPaidAdsConfirmationModal from './skip-paid-ads-confirmation-modal';
import { recordGlaEvent } from '.~/utils/tracks';

/**
 * Clicking on the skip paid ads button to complete the onboarding flow.
 * The 'unknown' value of properties may means:
 * - the final status has not yet been resolved when recording this event
 * - the status is not available, for example, the billing status is unknown if Google Ads account is not yet connected
 *
 * @event gla_onboarding_complete_button_click
 * @property {string} google_ads_account_status The connection status of merchant's Google Ads addcount, e.g. 'connected', 'disconnected', 'incomplete'
 * @property {string} billing_method_status The status of billing method of merchant's Google Ads addcount e.g. 'unknown', 'pending', 'approved', 'cancelled'
 * @property {string} campaign_form_validation Whether the entered paid campaign form data are valid, e.g. 'unknown', 'valid', 'invalid'
 */

export default function SkipButton( {
	isValidForm,
	onSkipCreatePaidAds = noop,
	loading,
	disabled,
} ) {
	const [
		showSkipPaidAdsConfirmationModal,
		setShowSkipPaidAdsConfirmationModal,
	] = useState( false );
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	const handleOnSkipClick = () => {
		setShowSkipPaidAdsConfirmationModal( true );
	};

	const handleCancelSkipPaidAdsClick = () => {
		setShowSkipPaidAdsConfirmationModal( false );
	};

	const handleSkipCreatePaidAds = () => {
		setShowSkipPaidAdsConfirmationModal( false );

		const eventProps = {
			google_ads_account_status: googleAdsAccount?.status,
			billing_method_status: billingStatus?.status || 'unknown',
			campaign_form_validation: isValidForm ? 'valid' : 'invalid',
		};
		recordGlaEvent( 'gla_onboarding_complete_button_click', eventProps );

		onSkipCreatePaidAds();
	};

	return (
		<>
			<AppButton
				isTertiary
				text={ __(
					'Skip paid ads creation',
					'google-listings-and-ads'
				) }
				loading={ loading }
				disabled={ disabled }
				onClick={ handleOnSkipClick }
			/>

			{ showSkipPaidAdsConfirmationModal && (
				<SkipPaidAdsConfirmationModal
					onRequestClose={ handleCancelSkipPaidAdsClick }
					onSkipCreatePaidAds={ handleSkipCreatePaidAds }
				/>
			) }
		</>
	);
}
