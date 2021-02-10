/**
 * Internal dependencies
 */
import useGoogleMCAccount from './useGoogleMCAccount';
import ConnectedCard from './connected-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';
import SpinnerCard from './spinner-card';

const SectionContent = ( props ) => {
	const { disabled } = props;
	const { googleMCAccount } = useGoogleMCAccount();

	if ( disabled ) {
		return <DisabledCard />;
	}

	if ( ! googleMCAccount ) {
		return <SpinnerCard />;
	}

	const { id } = googleMCAccount;

	if ( id === 0 ) {
		return <NonConnected />;
	}

	return <ConnectedCard googleMCAccount={ googleMCAccount } />;
};

export default SectionContent;
