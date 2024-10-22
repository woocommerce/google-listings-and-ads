/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AccountCreationDescription from './account-creation-description';
import AppSpinner from '../app-spinner';
import ConnectedIconLabel from '../connected-icon-label';
import { CREATING_BOTH_ACCOUNTS } from './constants';
import LoadingLabel from '../loading-label/loading-label';
import useAutoCreateAdsMCAccounts from '.~/hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useCreateMCAccount from '.~/hooks/useCreateMCAccount';
import ConnectMC from './connect-mc';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	// We are using a centralized hook which will be used when auto creating the MC account or manually creating a MC account
	// since we need to potentially show the reclaim url in a single place across both scenarios.
	const createMCAccount = useCreateMCAccount();

	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const {
		googleMCAccount,
		isPreconditionReady,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const {
		hasFinishedResolutionForExistingAdsMCAccounts,
		isCreatingWhichAccount,
		hasExistingMCAccount,
	} = useAutoCreateAdsMCAccounts( createMCAccount );

	const { hasAccess, step } = useGoogleAdsAccountStatus();

	const isCreatingAccounts = !! isCreatingWhichAccount;

	if (
		! hasFinishedResolutionForExistingAdsMCAccounts ||
		! hasFinishedResolutionForCurrentAdsAccount ||
		! hasFinishedResolutionForCurrentMCAccount
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isGoogleAdsConnected =
		hasGoogleAdsConnection &&
		hasAccess &&
		[ '', 'billing', 'link_merchant' ].includes( step );

	const isGoogleMCConnected =
		isPreconditionReady &&
		( googleMCAccount?.status === 'connected' ||
			( googleMCAccount?.status === 'incomplete' &&
				googleMCAccount?.step === 'link_ads' ) );

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

		if ( isGoogleAdsConnected && isGoogleMCConnected ) {
			return <ConnectedIconLabel />;
		}

		return null;
	};

	return (
		<>
			<AccountCard
				appearance={ APPEARANCE.GOOGLE }
				alignIcon="top"
				className="gla-google-combo-account-card--connected"
				description={
					<AccountCreationDescription
						isCreatingWhichAccount={ isCreatingWhichAccount }
					/>
				}
				helper={ getHelper() }
				indicator={ getIndicator() }
			/>

			{ hasExistingMCAccount && (
				<ConnectMC createMCAccount={ createMCAccount } />
			) }
		</>
	);
};

export default ConnectedGoogleComboAccountCard;
