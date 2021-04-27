/**
 * Internal dependencies
 */
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectedCard from './connected-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';
import SpinnerCard from '.~/components/spinner-card';

const SectionContent = ( props ) => {
	const { disabled } = props;
	const { hasFinishedResolution, googleMCAccount } = useGoogleMCAccount();

	if ( disabled ) {
		return <DisabledCard />;
	}

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( googleMCAccount.id === 0 || googleMCAccount.status !== 'connected' ) {
		return <NonConnected />;
	}

	return <ConnectedCard googleMCAccount={ googleMCAccount } />;
};

export default SectionContent;
