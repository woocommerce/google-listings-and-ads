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
import ClaimAdsAccount from './claim-ads-account';
import ConnectedIconLabel from '../connected-icon-label';
import ConversionMeasurementNotice from './conversion-measurement-notice';
import { CREATING_BOTH_ACCOUNTS } from './constants';
import LoadingLabel from '../loading-label/loading-label';
import useAutoCreateAdsMCAccounts from '../../hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
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
	} = useAutoCreateAdsMCAccounts();

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

	const getCardDescription = () => {
		return (
			<>
				<AccountCreationDescription
					isCreatingWhichAccount={ isCreatingWhichAccount }
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
