/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AppSpinner from '../app-spinner';
import useAutoCreateAdsMCAccounts from '../../hooks/useAutoCreateAdsMCAccounts';
import LoadingLabel from '../loading-label/loading-label';
import AccountCreationDescription from './account-creation-description';
import ConnectMC from './connect-mc';
import './connected-google-combo-account-card.scss';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectedIconLabel from '../connected-icon-label';
import useCreateMCAccount from '.~/hooks/useCreateMCAccount';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const createMCAccount = useCreateMCAccount();

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const {
		isConnected: isGoogleMCAccountConnected,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const {
		isCreatingAccounts,
		isCreatingBothAccounts,
		isCreatingOnlyAdsAccount,
		isCreatingOnlyMCAccount,
		accountCreationChecksResolved,
		accountsCreated,
	} = useAutoCreateAdsMCAccounts( createMCAccount );

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

	return (
		<div className="gla-google-combo-account-cards">
			<AccountCard
				appearance={ APPEARANCE.GOOGLE }
				alignIcon="top"
				className="gla-google-combo-account-card--connected"
				description={
					<AccountCreationDescription
						isCreatingBothAccounts={ isCreatingBothAccounts }
						isCreatingOnlyAdsAccount={ isCreatingOnlyAdsAccount }
						isCreatingOnlyMCAccount={ isCreatingOnlyMCAccount }
						isGoogleMCAccountConnected={
							isGoogleMCAccountConnected
						}
						isGoogleAdsAccountConnected={
							isGoogleAdsAccountConnected
						}
						accountsCreated={ accountsCreated }
					/>
				}
				helper={ getHelper() }
				indicator={ getIndicator() }
			/>

			<ConnectMC createMCAccount={ createMCAccount } />
		</div>
	);
};

export default ConnectedGoogleComboAccountCard;
