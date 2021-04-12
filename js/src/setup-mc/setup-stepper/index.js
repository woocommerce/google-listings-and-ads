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
	const mcSetup = useMCSetup();

	if ( ! mcSetup ) {
		return <AppSpinner />;
	}

	const { status, step } = mcSetup;

	if ( status === 'complete' ) {
		getHistory().push( getNewPath( {}, '/google/dashboard' ) );
		return null;
	}

	return <SavedSetupStepper savedStep={ stepNameKeyMap[ step ] } />;
};

export default SetupStepper;
