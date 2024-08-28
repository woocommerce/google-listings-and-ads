/**
 * External dependencies
 */
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import SavedSetupStepper from './saved-setup-stepper';
import useMCSetup from '.~/hooks/useMCSetup';
import useStoreAddress from '.~/hooks/useStoreAddress';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import stepNameKeyMap from './stepNameKeyMap';

const SetupStepper = () => {
	const { hasFinishedResolution, data: mcSetup } = useMCSetup();
	const { data: address, loaded: addressLoaded } = useStoreAddress();
	const { data: phone, loaded: phoneLoaded } = useGoogleMCPhoneNumber();

	const hasValidPhoneNumber =
		phoneLoaded && phone?.isValid && phone?.isVerified;

	const hasValidAddress =
		addressLoaded &&
		address?.isAddressFilled &&
		! address?.isMCAddressDifferent;

	const hasConfirmedStoreRequirements =
		hasValidPhoneNumber && hasValidAddress;

	const hasLoaded =
		hasFinishedResolution && mcSetup && addressLoaded && phoneLoaded;

	if ( hasFinishedResolution && ! mcSetup ) {
		// this means error occurred, we just need to return null here,
		// wp-data actions will display an error snackbar at the bottom of the page.
		return null;
	}

	if ( ! hasLoaded ) {
		return <AppSpinner />;
	}

	const { status } = mcSetup;
	const { step } = mcSetup;

	// If the user has already completed the store requirements, but is currently still on the
	// store requirements step, we should skip the store requirements step and go to the paid ads step.
	// else they will get stuck on a non-existent step #3
	const currentStep =
		step === 'store_requirements' && hasConfirmedStoreRequirements
			? 'paid_ads'
			: step;

	if ( status === 'complete' ) {
		getHistory().replace( getNewPath( {}, '/google/dashboard' ) );
		return null;
	}

	return (
		<SavedSetupStepper
			savedStep={ stepNameKeyMap[ currentStep ] }
			hasConfirmedStoreRequirements={ hasConfirmedStoreRequirements }
		/>
	);
};

export default SetupStepper;
