/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import AppButton from '.~/components/app-button';
import SkipPaidAdsConfirmationModal from './skip-paid-ads-confirmation-modal';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';

export default function SkipButton( {
	text,
	paidAds,
	onSkipCreatePaidAds = noop,
	completing,
} ) {
	const [
		showSkipPaidAdsConfirmationModal,
		setShowSkipPaidAdsConfirmationModal,
	] = useState( false );
	const { googleAdsAccount, hasGoogleAdsConnection } = useGoogleAdsAccount();
	const { status: billingStatus } = useGoogleAdsAccountBillingStatus();

	const handleOnSkipClick = () => {
		setShowSkipPaidAdsConfirmationModal( true );
	};

	const handleCancelSkipPaidAdsClick = () => {
		setShowSkipPaidAdsConfirmationModal( false );
	};

	const handleSkipCreatePaidAds = () => {
		setShowSkipPaidAdsConfirmationModal( false );
		onSkipCreatePaidAds();
	};

	const eventProps = {
		google_ads_account_status: googleAdsAccount?.status,
		billing_method_status: billingStatus || 'unknown',
		campaign_form_validation: paidAds?.isValid ? 'valid' : 'invalid',
	};

	const disabledSkip =
		completing === ACTION_COMPLETE || ! hasGoogleAdsConnection;

	return (
		<>
			<AppButton
				isTertiary
				data-action={ ACTION_SKIP }
				text={ text }
				loading={ completing === ACTION_SKIP }
				disabled={ disabledSkip }
				onClick={ handleOnSkipClick }
				eventName="gla_onboarding_complete_button_click"
				eventProps={ eventProps }
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
