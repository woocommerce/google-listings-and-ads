/**
 * Internal dependencies
 */
import FullScreen from '../components/full-screen';
import SetupAdsTopBar from './top-bar';
import AdsStepper from './ads-stepper';

const SetupAds = () => {
	return (
		<FullScreen>
			<SetupAdsTopBar />
			<AdsStepper />
		</FullScreen>
	);
};

export default SetupAds;
