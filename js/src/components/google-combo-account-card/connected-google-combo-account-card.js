/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CardDivider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import AppSpinner from '../app-spinner';
import useAutoCreateAdsMCAccounts from '../../hooks/useAutoCreateAdsMCAccounts';
import LoadingLabel from '../loading-label/loading-label';
import AccountCreationDescription from './account-creation-description';
import ConnectMC from './connect-mc';

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
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const {
		isCreatingAccounts,
		isCreatingBothAccounts,
		isCreatingAdsAccount,
		isCreatingMCAccount,
		accountCreationChecksResolved,
		accountsCreated,
	} = useAutoCreateAdsMCAccounts();

	// if (
	// 	! accountsCreated &&
	// 	( ! hasFinishedResolutionForCurrentAdsAccount ||
	// 		! hasFinishedResolutionForCurrentMCAccount ||
	// 		! accountCreationChecksResolved )
	// ) {
	// 	return <AccountCard description={ <AppSpinner /> } />;
	// }

	if (
		! accountsCreated &&
		( ! hasFinishedResolutionForCurrentMCAccount ||
			! accountCreationChecksResolved )
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

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

		return null;
	};

	return (
		<div className="gla-google-combo-account-card">
			<AccountCard
				appearance={ APPEARANCE.GOOGLE }
				className="gla-google-combo-account-card--connected"
				description={
					<AccountCreationDescription
						isLoading={
							! hasFinishedResolutionForCurrentAdsAccount ||
							! hasFinishedResolutionForCurrentMCAccount
						}
						isCreatingBothAccounts={ isCreatingBothAccounts }
						isCreatingAdsAccount={ isCreatingAdsAccount }
						isCreatingMCAccount={ isCreatingMCAccount }
						googleMCAccount={ googleMCAccount }
						googleAdsAccount={ googleAdsAccount }
					/>
				}
				helper={ getHelper() }
				indicator={ getIndicator() }
			/>
			<CardDivider />
			<ConnectMC />
		</div>
	);
};

export default ConnectedGoogleComboAccountCard;
