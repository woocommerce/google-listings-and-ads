/**
 * Internal dependencies
 */
import AppSpinner from '../../components/app-spinner';
import useGetOption from '../../hooks/useGetOption';
import SavedSetupStepper from './saved-setup-stepper';
import './index.scss';

const SetupStepper = () => {
	const { loading, data: savedStep } = useGetOption(
		'gla_setup_mc_saved_step'
	);

	if ( loading ) {
		return <AppSpinner />;
	}

	return <SavedSetupStepper savedStep={ savedStep } />;
};

export default SetupStepper;
