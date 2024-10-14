/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

const { APPROVED } = GOOGLE_ADS_BILLING_STATUS;

const LaunchPaidCampaignButton = ( { formProps, isLoading } ) => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	return (
		<AppButton
			isPrimary
			text={ __( 'Launch paid campaign', 'google-listings-and-ads' ) }
			disabled={
				! formProps.isValidForm || billingStatus?.status !== APPROVED
			}
			loading={ isLoading }
			onClick={ formProps.handleSubmit }
		/>
	);
};

export default LaunchPaidCampaignButton;
