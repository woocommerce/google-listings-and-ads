/**
 * Internal dependencies
 */
import CreateAccount from './create-account';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import SpinnerCard from '.~/components/spinner-card';
import ConnectAds from './connect-ads';

const NonConnected = () => {
	const { existingAccounts } = useExistingGoogleAdsAccounts();

	if ( ! existingAccounts ) {
		return <SpinnerCard />;
	}

	if ( existingAccounts.length === 0 ) {
		return <CreateAccount />;
	}

	return <ConnectAds />;
};

export default NonConnected;
