/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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

/**
 * Clicking on the "connect to a different Google account" button.
 *
 * @event gla_google_account_connect_different_account_button_click
 */

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 *
 * @param {Object} props React props.
 * @param {{ googleAccount: object }} props.googleAccount The Google account.
 *
 * @fires gla_google_account_connect_different_account_button_click
 */
const ConnectedGoogleComboAccountCard = ( { googleAccount } ) => {
	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const {
		accountsCreated,
		accountCreationChecksResolved,
		isCreatingAdsAccount,
		isCreatingMCAccount,
	} = useAutoCreateAdsMCAccounts();

	const isCreatingAccounts = isCreatingAdsAccount || isCreatingMCAccount;

	if (
		! accountsCreated &&
		( ! hasFinishedResolutionForCurrentAdsAccount ||
			! hasFinishedResolutionForCurrentMCAccount ||
			! accountCreationChecksResolved )
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const creatingAccounts =
		isCreatingAccounts ||
		( accountsCreated &&
			( ! hasFinishedResolutionForCurrentMCAccount ||
				! hasFinishedResolutionForCurrentAdsAccount ) );

	const getHelper = () =>
		isCreatingAdsAccount &&
		isCreatingMCAccount && (
			<p>
				{ __(
					'Merchant Center is required to sync products so they show on Google. Google Ads is required to set up conversion measurement for your store.',
					'google-listings-and-ads'
				) }
			</p>
		);

	const getIndicator = () => {
		if ( creatingAccounts ) {
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
					isCreatingAccounts={ creatingAccounts }
					isCreatingAdsAccount={ isCreatingAdsAccount }
					isCreatingMCAccount={ isCreatingMCAccount }
					googleMCAccount={ googleMCAccount }
					googleAdsAccount={ googleAdsAccount }
				/>
			}
			helper={ getHelper() }
			indicator={ getIndicator() }
		/>
	);
};

export default ConnectedGoogleComboAccountCard;
