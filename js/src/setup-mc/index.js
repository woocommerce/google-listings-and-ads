/**
 * Internal dependencies
 */
import FullScreen from '.~/components/full-screen';
import TopBar from './top-bar';
import SetupStepper from './setup-stepper';

const SetupMC = () => {
	return (
		<FullScreen>
			<TopBar />
			<SetupStepper />
		</FullScreen>
	);
};

export default SetupMC;
