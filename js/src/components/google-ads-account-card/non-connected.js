/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import CreateAccount from './create-account';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import ConnectAds from './connect-ads';

const NonConnected = () => {
	const { existingAccounts } = useExistingGoogleAdsAccounts();
	const [ ignoreExisting, setIgnoreExisting ] = useState( false );
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess } = useGoogleAdsAccountStatus();

	const handleShowExisting = () => {
		setIgnoreExisting( false );
	};

	if ( ! existingAccounts ) {
		return <SpinnerCard />;
	}

	if (
		existingAccounts.length === 0 ||
		ignoreExisting ||
		( googleAdsAccount.id && hasAccess !== true )
	) {
		return (
			<CreateAccount
				allowShowExisting={ ignoreExisting }
				onShowExisting={ handleShowExisting }
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
