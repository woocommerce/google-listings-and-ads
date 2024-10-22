/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AccountCardDescription, { Indicator } from './account-card-description';
import { AccountCreationContext } from './account-creation-context';
import AppSpinner from '../app-spinner';
import ClaimAdsAccount from './claim-ads-account';
import ConversionMeasurementNotice from './conversion-measurement-notice';
import useAutoCreateAdsMCAccounts from '.~/hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const { hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount } =
		useGoogleMCAccount();

	const { accountsCreated, hasDetermined, creatingWhich } =
		useAutoCreateAdsMCAccounts();

	if (
		! accountsCreated &&
		( ! hasDetermined ||
			! hasFinishedResolutionForCurrentAdsAccount ||
			! hasFinishedResolutionForCurrentMCAccount )
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const accountCreationData = {
		accountsCreated,
		creatingWhich,
	};

	const showSuccessNotice =
		googleAdsAccount.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ||
		googleAdsAccount.step === 'link_merchant';

	return (
		<AccountCreationContext.Provider value={ accountCreationData }>
			<AccountCard
				appearance={ APPEARANCE.GOOGLE }
				alignIcon="top"
				className="gla-google-combo-account-card--connected"
				helper={ <AccountCardDescription /> }
				indicator={ <Indicator /> }
			>
				{ showSuccessNotice && <ConversionMeasurementNotice /> }
				<ClaimAdsAccount />
			</AccountCard>
		</AccountCreationContext.Provider>
	);
};

export default ConnectedGoogleComboAccountCard;
