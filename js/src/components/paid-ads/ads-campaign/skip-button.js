/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { noop, merge } from 'lodash';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AppButton from '.~/components/app-button';
import { STORE_KEY } from '.~/data/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';

export default function SkipButton( {
	text,
	paidAds,
	showPaidAdsSetup,
	onClick = noop,
	completing,
} ) {
	const { googleAdsAccount, hasGoogleAdsConnection } = useGoogleAdsAccount();

	const eventProps = {
		opened_paid_ads_setup: 'no',
		google_ads_account_status: googleAdsAccount?.status,
		billing_method_status: 'unknown',
		campaign_form_validation: 'unknown',
	};

	if ( showPaidAdsSetup ) {
		const selector = select( STORE_KEY );
		const billing = selector.getGoogleAdsAccountBillingStatus();

		merge( eventProps, {
			opened_paid_ads_setup: 'yes',
			billing_method_status: billing?.status,
			campaign_form_validation: paidAds.isValid ? 'valid' : 'invalid',
		} );
	}

	const disabledSkip =
		completing === ACTION_COMPLETE || ! hasGoogleAdsConnection;

	return (
		<AppButton
			isTertiary
			data-action={ ACTION_SKIP }
			text={ text }
			loading={ completing === ACTION_SKIP }
			disabled={ disabledSkip }
			onClick={ onClick }
			eventName="gla_onboarding_complete_button_click"
			eventProps={ eventProps }
		/>
	);
}
