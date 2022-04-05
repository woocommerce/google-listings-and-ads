/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import SetupMCTopBar from './top-bar';
import SetupStepper from './setup-stepper';

const SetupMC = () => {
	useLayout( 'full-page' );

	return (
		<>
			<SetupMCTopBar />
			<SetupStepper />
		</>
	);
};

export default SetupMC;
