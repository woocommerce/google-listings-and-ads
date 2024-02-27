/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import CreateAccount from './create-account';
import useGoogleAccountCheck from '.~/hooks/useGoogleAccountCheck';
import ConnectAds from './connect-ads';

const NonConnected = ( { onCreateAccount = () => {} } ) => {
	const { google, existingAccounts, hasFinishedResolution } =
		useGoogleAccountCheck();
	const [ ignoreExisting, setIgnoreExisting ] = useState( false );

	const handleShowExisting = () => {
		setIgnoreExisting( false );
	};

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if (
		! existingAccounts ||
		existingAccounts.length === 0 ||
		ignoreExisting
	) {
		const disabled = ! google || google.active === 'no';
		return (
			<CreateAccount
				allowShowExisting={ ignoreExisting }
				onShowExisting={ handleShowExisting }
				disabled={ disabled }
				onCreateAccount={ onCreateAccount }
			/>
		);
	}

	const handleCreateNew = () => {
		setIgnoreExisting( true );
	};

	return (
		<ConnectAds
			accounts={ existingAccounts }
			onCreateNew={ handleCreateNew }
		/>
	);
};

export default NonConnected;
