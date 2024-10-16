/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

const { APPROVED } = GOOGLE_ADS_BILLING_STATUS;

/**
 * Renders the step to setup paid ads
 *
 * @param {Object} props Component props.
 * @param {boolean} props.isSubmitting Indicates if the form is currently being submitted.
 */
const SetupPaidAds = ( { isSubmitting } ) => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	return (
		<AdsCampaign
			headerTitle={ __(
				'Create your paid campaign',
				'google-listings-and-ads'
			) }
			context="setup-ads"
			continueButton={ ( formContext ) => (
				<AppButton
					isPrimary
					text={ __(
						'Launch paid campaign',
						'google-listings-and-ads'
					) }
					disabled={
						! formContext.isValidForm ||
						billingStatus?.status !== APPROVED
					}
					loading={ isSubmitting }
					onClick={ formContext.handleSubmit }
				/>
			) }
		/>
	);
};

export default SetupPaidAds;
