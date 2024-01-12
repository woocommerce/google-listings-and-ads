/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CreateAccount from './create-account';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import ConnectAds from './connect-ads';

const NonConnected = () => {
	const { google } = useGoogleAccount();
	const { existingAccounts } = useExistingGoogleAdsAccounts();
	const [ ignoreExisting, setIgnoreExisting ] = useState( false );

	const handleShowExisting = () => {
		setIgnoreExisting( false );
	};

	if (
		! existingAccounts ||
		existingAccounts.length === 0 ||
		ignoreExisting
	) {
		return (
			<CreateAccount
				allowShowExisting={ ignoreExisting }
				onShowExisting={ handleShowExisting }
				disabled={ ! google }
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
