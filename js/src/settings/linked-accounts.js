/**
 * External dependencies
 */
import { queueRecordEvent } from '@woocommerce/tracks';
import { __ } from '@wordpress/i18n';
import { Flex, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getGetStartedUrl } from '.~/utils/urls';
import useAdminUrl from '.~/hooks/useAdminUrl';
import useJetpackAccount from '.~/hooks/useJetpackAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import SpinnerCard from '.~/components/spinner-card';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import { ConnectedWPComAccountCard } from '.~/components/wpcom-account-card';
import { ConnectedGoogleAccountCard } from '.~/components/google-account-card';
import { ConnectedGoogleMCAccountCard } from '.~/components/google-mc-account-card';
import { ConnectedGoogleAdsAccountCard } from '.~/components/google-ads-account-card';
import Section from '.~/wcdl/section';
import LinkedAccountsSectionWrapper from './linked-accounts-section-wrapper';
import DisconnectModal, { ALL_ACCOUNTS, ADS_ACCOUNT } from './disconnect-modal';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';

const { CONNECTED, INCOMPLETE } = GOOGLE_ADS_ACCOUNT_STATUS;

/**
 * Accounts are disconnected from the Setting page
 *
 * @event gla_disconnected_accounts
 * @property {string} context (`all-accounts`|`ads-account`) - indicate which accounts have been disconnected.
 */

/**
 * @fires disconnected_accounts
 */
export default function LinkedAccounts() {
	const adminUrl = useAdminUrl();
	const { jetpack } = useJetpackAccount();
	const { google } = useGoogleAccount();
	const { googleMCAccount } = useGoogleMCAccount();
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
		queueRecordEvent( 'gla_disconnected_accounts', {
			context: openedModal,
		} );

		// Reload WC admin page to update the `glaData` initiated from the static script.
		const nextPage =
			openedModal === ALL_ACCOUNTS
				? adminUrl + getGetStartedUrl()
				: window.location.href;

		window.location.href = nextPage;
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
				<VerticalGapLayout size="large">
					<ConnectedWPComAccountCard jetpack={ jetpack } />
					<ConnectedGoogleAccountCard
						googleAccount={ google }
						hideAccountSwitch
					/>
					<ConnectedGoogleMCAccountCard
						googleMCAccount={ googleMCAccount }
						hideAccountSwitch
					/>
					{ hasAdsAccount && (
						<ConnectedGoogleAdsAccountCard
							googleAdsAccount={ googleAdsAccount }
							hideAccountSwitch
						>
							<Section.Card.Footer>
								<Button
									isDestructive
									isLink
									onClick={ openDisconnectAdsAccountModal }
								>
									{ __(
										'Disconnect Google Ads account only',
										'google-listings-and-ads'
									) }
								</Button>
							</Section.Card.Footer>
						</ConnectedGoogleAdsAccountCard>
					) }
					<Flex justify="flex-end">
						<Button
							isPrimary
							isDestructive
							onClick={ openDisconnectAllAccountsModal }
						>
							{ __(
								'Disconnect from all accounts',
								'google-listings-and-ads'
							) }
						</Button>
					</Flex>
				</VerticalGapLayout>
			) }
		</LinkedAccountsSectionWrapper>
	);
}
