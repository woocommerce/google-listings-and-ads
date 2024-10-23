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
import getAccountCreationTexts from '.~/utils/getAccountCreationTexts';
import useAutoCreateAdsMCAccounts from '.~/hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import useCreateMCAccount from '.~/hooks/useCreateMCAccount';
import ConnectMC from './connect-mc';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	// Used to track whether the account creation ever happened.
	const [ wasCreatingAccounts, setWasCreatingAccounts ] =
		useState( undefined );

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const { data: accounts } = useExistingGoogleMCAccounts();
	const [ createMCAccount, resultCreateMCAccount ] = useCreateMCAccount();
	const { hasDetermined, creatingWhich } =
		useAutoCreateAdsMCAccounts( createMCAccount );
	const { text, subText } = getAccountCreationTexts( wasCreatingAccounts );

	const accountDetailsResolved =
		hasDetermined &&
		hasFinishedResolutionForCurrentAdsAccount &&
		hasFinishedResolutionForCurrentMCAccount;

	const accountsCreated =
		wasCreatingAccounts !== undefined && ! creatingWhich;

	const accountsReady =
		accountDetailsResolved &&
		!! googleAdsAccount?.id &&
		!! googleMCAccount?.id;

	const displayAccountDetails = accountDetailsResolved && accountsReady;

	useEffect( () => {
		if ( creatingWhich ) {
			setWasCreatingAccounts( creatingWhich );
		}
	}, [ creatingWhich ] );

	if (
		! accountsCreated &&
		wasCreatingAccounts === undefined &&
		( ! hasDetermined ||
			! hasFinishedResolutionForCurrentAdsAccount ||
			! hasFinishedResolutionForCurrentMCAccount )
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	// Show the Connect MC card if there's no connected accounts and there are existing accounts.
	// The "Edit" button will be used to display the card within the connected state.
	// @TODO: review
	// const showConnectMCCard = ! hasGoogleMCConnection && accounts.length > 0;
	const showConnectMCCard = true;

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
				indicator={
					<Indicator showSpinner={ ! displayAccountDetails } />
				}
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
