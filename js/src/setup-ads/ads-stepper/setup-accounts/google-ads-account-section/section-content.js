/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Connected from './connected';
import NonConnected from './non-connected';
import AuthorizeAds from './authorize-ads';

const SectionContent = () => {
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

	return <Connected googleAdsAccount={ googleAdsAccount } />;
};

export default SectionContent;
