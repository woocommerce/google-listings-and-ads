/**
 * External dependencies
 */
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import SavedSetupStepper from './saved-setup-stepper';
import useMCSetup from '~/hooks/useMCSetup';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import SavedServiceBasedSetupStepper from './saved-service-based-setup-stepper';
import {
	STEP_NAME_KEY_MAP,
	SERVICE_BASED_STEP_NAME_KEY_MAP,
} from './constants';

const SetupStepper = () => {
	const { hasFinishedResolution, data: mcSetup } = useMCSetup();
	const serviceBasedMerchant = useServiceBasedMerchant();

	if ( ! hasFinishedResolution && ! mcSetup ) {
		return <AppSpinner />;
	}

	if ( hasFinishedResolution && ! mcSetup ) {
		// this means error occurred, we just need to return null here,
		// wp-data actions will display an error snackbar at the bottom of the page.
		return null;
	}

	const { status, step } = mcSetup;

	if ( status === 'complete' ) {
		getHistory().replace( getNewPath( {}, '/google/dashboard' ) );
		return null;
	}

	if ( serviceBasedMerchant ) {
		return (
			<SavedServiceBasedSetupStepper
				savedStep={ SERVICE_BASED_STEP_NAME_KEY_MAP[ step ] }
			/>
		);
	}

	return <SavedSetupStepper savedStep={ STEP_NAME_KEY_MAP[ step ] } />;
};

export default SetupStepper;
