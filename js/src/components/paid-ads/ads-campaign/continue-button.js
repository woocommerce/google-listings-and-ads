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
import { useAdaptiveFormContext } from '.~/components/adaptive-form';

/**
 * Renders the button to either continue through the Stepper or launch a paid campaign.
 *
 * @param {Object} props React props.
 * @param {boolean} props.setupAdsFlow Whether we are in the setup ads flow.
 * @param {() => void} props.onContinue Callback called once continue button is clicked.
 * @param {boolean} [props.isLoading] If true, the Continue button will display a loading spinner .
 */
const ContinueButton = ( { setupAdsFlow, onContinue, isLoading } ) => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { isValidForm } = useAdaptiveFormContext();

	let submitButtonText = __( 'Continue', 'google-listings-and-ads' );
	if ( setupAdsFlow ) {
		submitButtonText = __(
			'Launch paid campaign',
			'google-listings-and-ads'
		);
	}

	const isDisabled =
		! isValidForm ||
		( setupAdsFlow &&
			billingStatus?.status !== GOOGLE_ADS_BILLING_STATUS.APPROVED );

	return (
		<AppButton
			isPrimary
			disabled={ isDisabled }
			loading={ isLoading }
			onClick={ onContinue }
		>
			{ submitButtonText }
		</AppButton>
	);
};

export default ContinueButton;
