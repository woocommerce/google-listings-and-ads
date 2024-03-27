/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import CreateAccount from './create-account';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import ConnectAds from './connect-ads';

const NonConnected = () => {
	const { google, hasFinishedResolution } = useGoogleAccount();
	const { existingAccounts, isResolving } = useExistingGoogleAdsAccounts();
	const [ ignoreExisting, setIgnoreExisting ] = useState( false );

	const handleShowExisting = () => {
		setIgnoreExisting( false );
	};

	if ( ! hasFinishedResolution || isResolving ) {
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
