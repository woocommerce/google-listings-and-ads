/**
 * Internal dependencies
 */
import FullScreen from '.~/components/full-screen';
import TopBar from './top-bar';
import ListingsStepper from './listings-stepper';

const SetupMC = () => {
	return (
		<FullScreen>
			<TopBar />
			<ListingsStepper />
		</FullScreen>
	);
};

export default SetupMC;
