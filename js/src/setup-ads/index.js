/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import SetupAdsForm from './setup-ads-form';

const SetupAds = () => {
	useLayout( 'full-page' );

	return <SetupAdsForm />;
};

export default SetupAds;
