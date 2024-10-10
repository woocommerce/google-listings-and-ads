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
import { ClaimAdsAccount } from './claim-ads-account';
import {
	GOOGLE_ADS_ACCOUNT_STATUS,
	GOOGLE_MC_ACCOUNT_STATUS,
} from '.~/constants';

/**
 * Clicking on the "connect to a different Google account" button.
 *
 * @event gla_google_account_connect_different_account_button_click
 */

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 *
 * @fires gla_google_account_connect_different_account_button_click
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
		isCreatingAdsAccount,
		isCreatingMCAccount,
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
			return (
				<ConnectedIconLabel
					text={ __( 'Connected', 'google-listings-and-ads' ) }
				/>
			);
		}

		return null;
	};

	const getCardDescription = () => {
		return (
			<>
				<AccountCreationDescription
					isCreatingBothAccounts={ isCreatingBothAccounts }
					isCreatingAdsAccount={ isCreatingAdsAccount }
					isCreatingMCAccount={ isCreatingMCAccount }
					accountsCreated={ accountsCreated }
				/>
			</>
		);
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			className="gla-google-combo-account-card--connected"
			description={ getCardDescription() }
			helper={ getHelper() }
			indicator={ getIndicator() }
		>
			<ClaimAdsAccount />
		</AccountCard>
	);
};

export default ConnectedGoogleComboAccountCard;
