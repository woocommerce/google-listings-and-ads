/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CreateAccount from './create-account';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import SpinnerCard from '.~/components/spinner-card';
import ConnectAds from './connect-ads';

const NonConnected = () => {
	const { existingAccounts } = useExistingGoogleAdsAccounts();
	const [ ignoreExisting, setIgnoreExisting ] = useState( false );

	if ( ! existingAccounts ) {
		return <SpinnerCard />;
	}

	const handleShowExisting = () => {
		setIgnoreExisting( false );
	};

	if ( existingAccounts.length === 0 || ignoreExisting ) {
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
