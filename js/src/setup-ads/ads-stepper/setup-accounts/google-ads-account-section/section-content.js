/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Connected from './connected';
import NonConnected from './non-connected';

const SectionContent = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();

	if ( ! googleAdsAccount ) {
		return <SpinnerCard />;
	}

	if ( googleAdsAccount.status === 'disconnected' ) {
		return <NonConnected />;
	}

	return <Connected googleAdsAccount={ googleAdsAccount } />;
};

export default SectionContent;
