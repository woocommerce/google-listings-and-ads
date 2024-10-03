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
		isCreatingAccounts,
		isCreatingBothAccounts,
		isCreatingAdsAccount,
		isCreatingMCAccount,
		accountCreationChecksResolved,
		accountsCreated,
	} = useAutoCreateAdsMCAccounts();

	if ( ! accountCreationChecksResolved ) {
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
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			className="gla-google-combo-account-card--connected"
			description={
				<AccountCreationDescription
					isCreatingBothAccounts={ isCreatingBothAccounts }
					isCreatingAdsAccount={ isCreatingAdsAccount }
					isCreatingMCAccount={ isCreatingMCAccount }
					accountsCreated={ accountsCreated }
				/>
			}
			helper={ getHelper() }
			indicator={ getIndicator() }
		/>
	);
};

export default ConnectedGoogleComboAccountCard;
