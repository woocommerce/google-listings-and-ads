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
		data: mcSetup,
		invalidateResolution: mcSetupInvalidateResolution,
	} = useMCSetup();

	if ( ! mcSetup ) {
		return <AppSpinner />;
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
