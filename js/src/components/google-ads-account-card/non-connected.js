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

const useGoogleAccountCheck = () => {
	const { google } = useGoogleAccount();
	const { existingAccounts } =
		google.active !== 'no'
			? // eslint-disable-next-line react-hooks/rules-of-hooks
			  useExistingGoogleAdsAccounts()
			: { existingAccounts: null };

	return { google, existingAccounts };
};

const NonConnected = () => {
	const { google, existingAccounts } = useGoogleAccountCheck();
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
