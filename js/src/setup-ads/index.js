/**
 * Internal dependencies
 */
import FullScreen from '.~/components/full-screen';
import SetupAdsTopBar from './top-bar';
import SetupAdsForm from './setup-ads-form';

const SetupAds = () => {
	return (
		<FullScreen>
			<SetupAdsTopBar />
			<SetupAdsForm />
		</FullScreen>
	);
};

export default SetupAds;
