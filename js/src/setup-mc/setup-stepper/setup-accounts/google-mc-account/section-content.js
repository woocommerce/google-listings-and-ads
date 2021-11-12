/**
 * Internal dependencies
 */
import ConnectedCard from './connected-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';

const SectionContent = ( props ) => {
	const { googleMCAccount } = props;

	/**
	 * If there is no googleMCAccount, this means users have not connected their Google account,
	 * or have not granted necessary access permissions for Google Merchant Center,
	 * so we show a DisabledCard here.
	 */
	if ( ! googleMCAccount ) {
		return <DisabledCard />;
	}

	if ( googleMCAccount.id === 0 || googleMCAccount.status !== 'connected' ) {
		return <NonConnected />;
	}

	return <ConnectedCard googleMCAccount={ googleMCAccount } />;
};

export default SectionContent;
