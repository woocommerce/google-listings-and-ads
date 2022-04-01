/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ConnectedGoogleAdsAccountCard from './connected-google-ads-account-card';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';

export default function GoogleAdsAccountCard() {
	const { google, scope } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();

	if ( ! google || ! googleAdsAccount ) {
		return <SpinnerCard />;
	}

	if ( ! scope.adsRequired ) {
		return <AuthorizeAds additionalScopeEmail={ google.email } />;
	}

	if ( googleAdsAccount.status === 'disconnected' ) {
		return <NonConnected />;
	}

	return (
		<ConnectedGoogleAdsAccountCard googleAdsAccount={ googleAdsAccount } />
	);
}
