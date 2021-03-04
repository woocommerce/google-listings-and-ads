/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectedCard from './connected-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';

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
