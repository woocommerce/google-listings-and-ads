/**
 * Internal dependencies
 */
import FullScreen from '.~/components/full-screen';
import SetupMCTopBar from './top-bar';
import SetupStepper from './setup-stepper';

const SetupMC = () => {
	return (
		<FullScreen>
			<SetupMCTopBar />
			<SetupStepper />
		</FullScreen>
	);
};

export default SetupMC;
