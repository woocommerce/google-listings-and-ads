/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AccountDetails from './account-details';
<<<<<<< HEAD
import AppSpinner from '../app-spinner';
import ClaimAdsAccount from './claim-ads-account';
import ConversionMeasurementNotice from './conversion-measurement-notice';
=======
>>>>>>> feature/2567-kickoff-mc-ads-account-creation
import Indicator from './indicator';
import getAccountCreationTexts from './getAccountCreationTexts';
import SpinnerCard from '.~/components/spinner-card';
import useAutoCreateAdsMCAccounts from '.~/hooks/useAutoCreateAdsMCAccounts';
<<<<<<< HEAD
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
=======
>>>>>>> feature/2567-kickoff-mc-ads-account-creation
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const { hasDetermined, creatingWhich } = useAutoCreateAdsMCAccounts();
	const { text, subText } = getAccountCreationTexts( creatingWhich );

	if ( ! hasDetermined ) {
		return <SpinnerCard />;
	}

	return (
		<>
			<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			alignIcon="top"
			className="gla-google-combo-account-card--connected"
			description={ text || <AccountDetails /> }
			helper={ subText }
			indicator={ <Indicator showSpinner={ Boolean( creatingWhich ) } /> }
		>
				<ConversionMeasurementNotice />
				<ClaimAdsAccount />
			</AccountCard>
		</>
	);
};

export default ConnectedGoogleComboAccountCard;
