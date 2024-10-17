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
import { CREATING_BOTH_ACCOUNTS } from '.~/constants';

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

	const { accountCreationChecksResolved, isCreatingWhichAccount } =
		useAutoCreateAdsMCAccounts();

	const isCreatingAccounts = !! isCreatingWhichAccount;

	if (
		! accountCreationChecksResolved ||
		! hasFinishedResolutionForCurrentAdsAccount ||
		! hasFinishedResolutionForCurrentMCAccount
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isGoogleAdsReady = ! isCreatingAccounts && googleAdsAccount.id > 0;
	const isGoogleMCReady = ! isCreatingAccounts && googleMCAccount.id > 0;

	const getHelper = () => {
		if ( isCreatingWhichAccount === CREATING_BOTH_ACCOUNTS ) {
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

		if ( isGoogleAdsReady && isGoogleMCReady ) {
			return <ConnectedIconLabel />;
		}

		return null;
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			alignIcon="top"
			className="gla-google-combo-account-card--connected"
			description={
				<AccountCreationDescription
					isCreatingWhichAccount={ isCreatingWhichAccount }
					isGoogleAdsReady={ isGoogleAdsReady }
					isGoogleMCReady={ isGoogleMCReady }
				/>
			}
			helper={ getHelper() }
			indicator={ getIndicator() }
		/>
	);
};

export default ConnectedGoogleComboAccountCard;
