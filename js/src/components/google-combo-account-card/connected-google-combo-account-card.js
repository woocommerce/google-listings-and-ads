/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AccountDetails from './account-details';
import AppSpinner from '../app-spinner';
import Indicator from './indicator';
import getAccountCreationTexts from './getAccountCreationTexts';
import useAutoCreateAdsMCAccounts from '.~/hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import useCreateMCAccount from '.~/hooks/useCreateMCAccount';
import useGoogleMCAccountReady from '.~/hooks/useGoogleMCAccountReady';
import ConnectMC from './connect-mc';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const isGoogleMCConnected = useGoogleMCAccountReady();
	// Used to track whether the account creation ever happened.
	const [ wasCreatingAccounts, setWasCreatingAccounts ] =
		useState( undefined );

	const { hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount } =
		useGoogleAdsAccount();

	const { hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount } =
		useGoogleMCAccount();

	// We use a single instance of the hook to create a MC (Merchant Center) account,
	// ensuring consistent results across both the main component (ConnectedGoogleComboAccountCard)
	// and its child component (ConnectMC).
	// This approach is especially useful when an MC account is automatically created,
	// and the URL needs to be reclaimed. The URL reclaiming component is rendered
	// within the ConnectMC component.
	const [ createMCAccount, resultCreateMCAccount ] = useCreateMCAccount();
	const { data: accounts } = useExistingGoogleMCAccounts();
	const { hasDetermined, creatingWhich } =
		useAutoCreateAdsMCAccounts( createMCAccount );
	const { text, subText } = getAccountCreationTexts( wasCreatingAccounts );

	const accountDetailsResolved =
		hasDetermined &&
		hasFinishedResolutionForCurrentAdsAccount &&
		hasFinishedResolutionForCurrentMCAccount;

	const displayAccountDetails =
		accountDetailsResolved && wasCreatingAccounts === null;

	useEffect( () => {
		if ( hasDetermined ) {
			setWasCreatingAccounts( creatingWhich );
		}
	}, [ creatingWhich, hasDetermined ] );

	if (
		wasCreatingAccounts === undefined &&
		( ! hasDetermined ||
			! hasFinishedResolutionForCurrentAdsAccount ||
			! hasFinishedResolutionForCurrentMCAccount )
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	// Show the Connect MC card if there's no connected accounts and there are existing accounts.
	// The "Edit" button will be used to display the card within the connected state.
	const showConnectMCCard = ! isGoogleMCConnected && accounts.length > 0;

	return (
		<div className="gla-google-combo-account-cards">
			<AccountCard
				appearance={ APPEARANCE.GOOGLE }
				alignIcon="top"
				className="gla-google-combo-account-card--connected"
				description={
					! displayAccountDetails ? text : <AccountDetails />
				}
				helper={ ! displayAccountDetails ? subText : null }
				indicator={ <Indicator showSpinner={ creatingWhich } /> }
			/>

			{ showConnectMCCard && (
				<ConnectMC
					createMCAccount={ createMCAccount }
					resultCreateMCAccount={ resultCreateMCAccount }
				/>
			) }
		</div>
	);
};

export default ConnectedGoogleComboAccountCard;
