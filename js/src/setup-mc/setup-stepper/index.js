/**
 * External dependencies
 */
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import SavedSetupStepper from './saved-setup-stepper';
import './index.scss';
import useMCSetup from '.~/hooks/useMCSetup';
import stepNameKeyMap from './stepNameKeyMap';

const SetupStepper = () => {
	const {
		hasFinishedResolution,
		data: mcSetup,
		invalidateResolution: mcSetupInvalidateResolution,
	} = useMCSetup();

	if ( ! hasFinishedResolution && ! mcSetup ) {
		return <AppSpinner />;
	}

	if ( hasFinishedResolution && ! mcSetup ) {
		// this means error occurred, we just need to return null here,
		// wp-data actions will display an error notice at the bottom of the page.
		return null;
	}

	const { status, step } = mcSetup;

	if ( status === 'complete' ) {
		getHistory().replace( getNewPath( {}, '/google/dashboard' ) );
		return null;
	}

	const handleRefetchSavedStep = () => {
		mcSetupInvalidateResolution();
	};

	return (
		<SavedSetupStepper
			savedStep={ stepNameKeyMap[ step ] }
			onRefetchSavedStep={ handleRefetchSavedStep }
		/>
	);
};

export default SetupStepper;
