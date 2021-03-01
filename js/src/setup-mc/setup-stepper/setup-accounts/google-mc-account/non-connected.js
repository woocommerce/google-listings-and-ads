/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ConnectMCCard from './connect-mc-card';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import SpinnerCard from './spinner-card';
import CreateAccount from './create-account';

const NonConnected = () => {
	const { existingAccounts } = useExistingGoogleMCAccounts();
	const [ ignoreExisting, setIgnoreExisting ] = useState( false );

	if ( ! existingAccounts ) {
		return <SpinnerCard />;
	}

	if ( existingAccounts.length === 0 || ignoreExisting ) {
		return <CreateAccount />;
	}

	const handleCreateNew = () => {
		setIgnoreExisting( true );
	};

	return <ConnectMCCard onCreateNew={ handleCreateNew } />;
};

export default NonConnected;
