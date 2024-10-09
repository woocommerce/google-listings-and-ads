/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import AppButton from '.~/components/app-button';

/**
 * ContinueButton Component.
 *
 * Renders a button labeled "Continue" that is enabled only if the form is valid
 * and the Google Ads billing status is completed. Calls the `onClick` handler when clicked.
 *
 * @param {Object} props Component props.
 * @param {Object} props.formProps Properties related to the form.
 * @param {Function} props.onClick Callback function to be executed when the button is clicked.
 */
const ContinueButton = ( { formProps, onClick } ) => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	return (
		<AppButton
			isPrimary
			text={ __( 'Continue', 'google-listings-and-ads' ) }
			onClick={ onClick }
			disabled={ ! formProps.isValidForm || ! isBillingCompleted }
		/>
	);
};

export default ContinueButton;
