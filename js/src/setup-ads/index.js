/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import SetupAdsTopBar from './top-bar';
import AdsStepper from './ads-stepper';

const SetupAds = () => {
	useLayout( 'full-page' );

	return (
		<>
			<SetupAdsTopBar />
			<AdsStepper />
		</>
	);
};

export default SetupAds;
