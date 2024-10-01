/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import Gridiconcheckmark from 'gridicons/dist/checkmark';
import { Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import SpinnerCard from '.~/components/spinner-card';
import BillingSetupCard from './billing-setup-card';
import fallbackBillingUrl from './fallbackBillingUrl';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';
import './billing-card.scss';

const { APPROVED, PENDING } = GOOGLE_ADS_BILLING_STATUS;

/**
 * Renders a success notice card or a setup card according to the billing status
 * of the connected Google Ads account.
 */
export default function BillingCard() {
	const [ showNotice, setShowNotice ] = useState( false );
	const { billingStatus, hasFinishedResolution } =
		useGoogleAdsAccountBillingStatus();

	useEffect( () => {
		// only show notice if we are in pending state before
		if ( billingStatus.status === PENDING && ! showNotice ) {
			setShowNotice( true );
		}
	}, [ billingStatus, showNotice ] );

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( billingStatus.status === APPROVED ) {
		if ( ! showNotice ) {
			return null;
		}

		return (
			<Flex className="gla-google-ads-billing-card__success-status">
				<Gridiconcheckmark size={ 18 } />
				<FlexBlock>
					{ __(
						'Billing method for Google Ads added successfully',
						'google-listings-and-ads'
					) }
				</FlexBlock>
			</Flex>
		);
	}

	return (
		<BillingSetupCard
			billingUrl={ billingStatus.billing_url || fallbackBillingUrl }
		/>
	);
}
