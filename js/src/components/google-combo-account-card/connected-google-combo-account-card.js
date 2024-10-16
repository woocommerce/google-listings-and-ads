/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AppSpinner from '../app-spinner';
import useAutoCreateAdsMCAccounts from '../../hooks/useAutoCreateAdsMCAccounts';
import LoadingLabel from '../loading-label/loading-label';
import AccountCreationDescription from './account-creation-description';
import './connected-google-combo-account-card.scss';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectedIconLabel from '../connected-icon-label';
import ConversionMeasurementNotice from './conversion-measurement-notice';
import { ClaimAdsAccount } from './claim-ads-account';
import {
	GOOGLE_ADS_ACCOUNT_STATUS,
	GOOGLE_MC_ACCOUNT_STATUS,
} from '.~/constants';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const {
		isCreatingAccounts,
		isCreatingBothAccounts,
		isCreatingOnlyAdsAccount,
		isCreatingOnlyMCAccount,
		accountCreationChecksResolved,
		accountsCreated,
	} = useAutoCreateAdsMCAccounts();

	if (
		! accountCreationChecksResolved ||
		! hasFinishedResolutionForCurrentAdsAccount ||
		! hasFinishedResolutionForCurrentMCAccount
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isGoogleAdsAccountConnected =
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ||
		( googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.INCOMPLETE &&
			[ 'link_merchant', 'account_access' ].includes(
				googleAdsAccount?.step
			) );

	const isGoogleMCAccountConnected =
		googleMCAccount?.id ||
		googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.CONNECTED ||
		( googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE &&
			[ 'link_ads', 'claim' ].includes( googleMCAccount?.step ) );

	const getHelper = () => {
		if ( isCreatingBothAccounts ) {
			return (
				<p>
					{ __(
						'Merchant Center is required to sync products so they show on Google. Google Ads is required to set up conversion measurement for your store.',
						'google-listings-and-ads'
					) }
				</p>
			);
		}

		return null;
	};

	const getIndicator = () => {
		if ( isCreatingAccounts ) {
			return (
				<LoadingLabel
					text={ __( 'Creating…', 'google-listings-and-ads' ) }
				/>
			);
		}

		if ( isGoogleAdsAccountConnected && isGoogleMCAccountConnected ) {
			return <ConnectedIconLabel />;
		}

		return null;
	};

	const getCardDescription = () => {
		return (
			<>
				<AccountCreationDescription
					isCreatingBothAccounts={ isCreatingBothAccounts }
					isCreatingOnlyAdsAccount={ isCreatingOnlyAdsAccount }
					isCreatingOnlyMCAccount={ isCreatingOnlyMCAccount }
					accountsCreated={ accountsCreated }
				/>
			</>
		);
	};

	const showSuccessNotice =
		googleAdsAccount.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ||
		googleAdsAccount.step === 'link_merchant';

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			className="gla-google-combo-account-card--connected"
			description={ getCardDescription() }
			helper={ getHelper() }
			indicator={ getIndicator() }
		>
			{ showSuccessNotice && <ConversionMeasurementNotice /> }
			<ClaimAdsAccount />
		</AccountCard>
	);
};

export default ConnectedGoogleComboAccountCard;
