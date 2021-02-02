/**
 * Internal dependencies
 */
import FullScreen from '../components/full-screen';
import TopBar from './top-bar';
import AdsStepper from './ads-stepper';

const SetupAds = () => {
	return (
		<FullScreen>
			<TopBar />
			<AdsStepper />
		</FullScreen>
	);
};

export default SetupAds;
