/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getGetStartedUrl } from '~/utils/urls';
import useAdminUrl from '~/hooks/useAdminUrl';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import AppButton from '~/components/app-button';
import SpinnerCard from '~/components/spinner-card';
import { ConnectedWPComAccountCard } from '~/components/wpcom-account-card';
import {
	ConnectedGoogleAccountCard,
	AuthorizeWPComAppCard,
} from '~/components/google-account-card';
import { ConnectedGoogleAdsAccountCard } from '~/components/google-ads-account-card';
import { MerchantCenterAccountInfoCard } from '~/components/google-mc-account-card';
import Section from '~/components/section';
import LinkedAccountsSectionWrapper from './linked-accounts-section-wrapper';
import DisconnectModal, { ALL_ACCOUNTS, ADS_ACCOUNT } from './disconnect-modal';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '~/constants';
import { queueRecordGlaEvent } from '~/utils/tracks';

const { CONNECTED, INCOMPLETE } = GOOGLE_ADS_ACCOUNT_STATUS;

/**
 * Accounts are disconnected from the Setting page
 *
 * @event gla_disconnected_accounts
 * @property {string} context (`all-accounts`|`ads-account`) - indicate which accounts have been disconnected.
 */

/**
 * @fires gla_disconnected_accounts
 */
export default function LinkedAccounts() {
	const adminUrl = useAdminUrl();
	const { jetpack } = useJetpackAccount();
	const { google } = useGoogleAccount();
	const { googleMCAccount, isWPComAppGranted } = useGoogleMCAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();

	const isLoading = ! (
		jetpack &&
		google &&
		googleMCAccount &&
		googleAdsAccount
	);
	const hasAdsAccount = [ CONNECTED, INCOMPLETE ].includes(
		googleAdsAccount?.status
	);

	const [ openedModal, setOpenedModal ] = useState( null );
	const openDisconnectAllAccountsModal = () => setOpenedModal( ALL_ACCOUNTS );
	const openDisconnectAdsAccountModal = () => setOpenedModal( ADS_ACCOUNT );
	const dismissModal = () => setOpenedModal( null );

	const handleDisconnected = () => {
		queueRecordGlaEvent( 'gla_disconnected_accounts', {
			context: openedModal,
		} );

		// Reload WC admin page to update the `glaData` initiated from the static script.
		const nextPage =
			openedModal === ALL_ACCOUNTS
				? adminUrl + getGetStartedUrl()
				: window.location.href;

		window.location.href = nextPage;
	};

	const renderGoogleAccountCard = () => {
		// The initial value of `wpcom_rest_api_status` is `null`, in which case it
		// will be handled by the `EnableNewProductSyncNotice` component. Therefore,
		// here checking it to avoid the `AuthorizeWPComAppCard` component being
		// duplically rendered together with `EnableNewProductSyncNotice`.
		if (
			googleMCAccount.wpcom_rest_api_status &&
			googleMCAccount.notification_service_enabled &&
			! isWPComAppGranted
		) {
			return (
				<AuthorizeWPComAppCard
					hideAccountSwitch
					eventPropsOfEnableButton={ {
						page: 'settings',
						context: 'mc_card',
					} }
				/>
			);
		}

		return (
			<ConnectedGoogleAccountCard
				googleAccount={ google }
				hideAccountSwitch
			>
				{ isWPComAppGranted && (
					<Notice status="success" isDismissible={ false }>
						{ __(
							'Google has been granted access to fetch your product data.',
							'google-listings-and-ads'
						) }
					</Notice>
				) }
			</ConnectedGoogleAccountCard>
		);
	};

	return (
		<LinkedAccountsSectionWrapper>
			{ openedModal && (
				<DisconnectModal
					onRequestClose={ dismissModal }
					onDisconnected={ handleDisconnected }
					disconnectTarget={ openedModal }
				/>
			) }
			{ isLoading ? (
				<SpinnerCard />
			) : (
				<>
					<ConnectedWPComAccountCard jetpack={ jetpack } />
					{ renderGoogleAccountCard() }
					<MerchantCenterAccountInfoCard
						googleMCAccount={ googleMCAccount }
					/>
					{ hasAdsAccount && (
						<ConnectedGoogleAdsAccountCard
							googleAdsAccount={ googleAdsAccount }
							hideAccountSwitch
						>
							<Section.Card.Footer>
								<AppButton
									isDestructive
									isLink
									onClick={ openDisconnectAdsAccountModal }
								>
									{ __(
										'Disconnect Google Ads account only',
										'google-listings-and-ads'
									) }
								</AppButton>
							</Section.Card.Footer>
						</ConnectedGoogleAdsAccountCard>
					) }
					<Flex justify="flex-end">
						<AppButton
							isPrimary
							isDestructive
							onClick={ openDisconnectAllAccountsModal }
						>
							{ __(
								'Disconnect from all accounts',
								'google-listings-and-ads'
							) }
						</AppButton>
					</Flex>
				</>
			) }
		</LinkedAccountsSectionWrapper>
	);
}
