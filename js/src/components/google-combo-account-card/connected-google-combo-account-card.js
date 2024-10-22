/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AccountCardDescription, { Indicator } from './account-card-description';
import { AccountCreationContext } from './account-creation-context';
import AppSpinner from '../app-spinner';
import useAutoCreateAdsMCAccounts from '../../hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const { hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount } =
		useGoogleAdsAccount();

	const { hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount } =
		useGoogleMCAccount();

	const {
		accountsCreated,
		hasFinishedResolutionForExistingAdsMCAccounts,
		isCreatingWhichAccount,
	} = useAutoCreateAdsMCAccounts();

	if (
		! accountsCreated &&
		( ! hasFinishedResolutionForExistingAdsMCAccounts ||
			! hasFinishedResolutionForCurrentAdsAccount ||
			! hasFinishedResolutionForCurrentMCAccount )
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const accountCreationData = {
		accountsCreated,
		creatingAccounts: isCreatingWhichAccount,
	};

	return (
		<AccountCreationContext.Provider value={ accountCreationData }>
			<AccountCard
				appearance={ APPEARANCE.GOOGLE }
				alignIcon="top"
				className="gla-google-combo-account-card--connected"
				helper={ <AccountCardDescription /> }
				indicator={ <Indicator /> }
			/>
		</AccountCreationContext.Provider>
	);
};

export default ConnectedGoogleComboAccountCard;
