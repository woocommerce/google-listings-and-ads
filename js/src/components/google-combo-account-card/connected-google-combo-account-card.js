/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import ConnectAds from './connect-ads';
import AccountDetails from './account-details';
import AppSpinner from '../app-spinner';
import Indicator from './indicator';
import getAccountCreationTexts from './getAccountCreationTexts';
import useAutoCreateAdsMCAccounts from '.~/hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	// Used to track whether the account creation ever happened.
	const [ wasCreatingAccounts, setWasCreatingAccounts ] =
		useState( undefined );

	const { hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount } =
		useGoogleAdsAccount();

	const { hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount } =
		useGoogleMCAccount();

	const { hasDetermined, creatingWhich } = useAutoCreateAdsMCAccounts();
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

	return (
		<div className="gla-google-combo-account-card gla-account-card">
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
			<ConnectAds />
		</div>
	);
};

export default ConnectedGoogleComboAccountCard;
