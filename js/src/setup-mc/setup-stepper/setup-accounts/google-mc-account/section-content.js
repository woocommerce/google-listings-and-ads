/**
 * Internal dependencies
 */
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import ConnectedCard from './connected-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';
import ConnectMCCard from './connect-mc-card';
import SpinnerCard from '.~/components/spinner-card';

const MaybePreviewConnectMCCard = () => {
	const {
		existingAccounts,
		hasFinishedResolution,
	} = useExistingGoogleMCAccounts();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( existingAccounts?.length > 0 ) {
		return <ConnectMCCard disabled />;
	}
	return <DisabledCard />;
};

const SectionContent = ( props ) => {
	const { disabled, maybePreviewExistingAccounts } = props;
	const { hasFinishedResolution, googleMCAccount } = useGoogleMCAccount();
	const isNonConnected =
		googleMCAccount?.id === 0 || googleMCAccount?.status !== 'connected';

	if ( disabled ) {
		if ( maybePreviewExistingAccounts && isNonConnected ) {
			return <MaybePreviewConnectMCCard />;
		}
		return <DisabledCard />;
	}

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( isNonConnected ) {
		return <NonConnected />;
	}

	return <ConnectedCard googleMCAccount={ googleMCAccount } />;
};

export default SectionContent;
