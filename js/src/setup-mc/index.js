/**
 * External dependencies
 */
import FullScreen from 'root/components/full-screen';

/**
 * Internal dependencies
 */
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
