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
import stepNameKeyMap from './stepNameKeyMap';

const SetupStepper = () => {
	const { hasFinishedResolution, data: mcSetup } = useMCSetup();

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

	return <SavedSetupStepper savedStep={ stepNameKeyMap[ step ] } />;
};

export default SetupStepper;
