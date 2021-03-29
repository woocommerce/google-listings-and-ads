/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import useJetpackAccount from '.~/hooks/useJetpackAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AppSpinner from '.~/components/app-spinner';
import Section from '.~/wcdl/section';
import AccountSubsection from './account-subsection';
import DisconnectModal from './disconnect-modal';
import { ALL_ACCOUNTS, ADS_ACCOUNT } from './constants';

export default function DisconnectAccounts() {
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
	const hasAdsAccount = [ 'connected', 'incomplete' ].includes(
		googleAdsAccount?.status
	);

	const [ openedModal, setOpenedModal ] = useState( null );
	const openDisconnectAllAccountsModal = () => setOpenedModal( ALL_ACCOUNTS );
	const openDisconnectAdsAccountModal = () => setOpenedModal( ADS_ACCOUNT );
	const dismissModal = () => setOpenedModal( null );

	const handleDisconnected = () => {
		// The re-fetch of Google ads account will be triggered within the resolvers.
		// Here only need to handle the all accounts disconnection case.
		if ( openedModal === ALL_ACCOUNTS ) {
			// Force reload WC admin page to initiate the Get Started page.
			const path = `/wp-admin/${ getNewPath( null, '/google/start' ) }`;
			window.location.href = path;
		}
	};

	const requiredText = __( 'Required', 'google-listings-and-ads' );

	return (
		<Section
			title={ __( 'Linked accounts', 'google-listings-and-ads' ) }
			description={ __(
				'A Wordpress.com account, Google account, and Google Merchant Center account are required to use this extension in WooCommerce.',
				'google-listings-and-ads'
			) }
		>
			{ openedModal && (
				<DisconnectModal
					onRequestClose={ dismissModal }
					onDisconnected={ handleDisconnected }
					disconnectTarget={ openedModal }
				/>
			) }
			<Section.Card>
				{ isLoading ? (
					<AppSpinner />
				) : (
					<>
						<Section.Card.Body>
							<AccountSubsection
								title={ __(
									'WordPress.com',
									'google-listings-and-ads'
								) }
								info={ jetpack.email }
								helperContent={ requiredText }
							/>
							<AccountSubsection
								title={ __(
									'Google',
									'google-listings-and-ads'
								) }
								info={ google.email }
								helperContent={ requiredText }
							/>
							<AccountSubsection
								title={ __(
									'Google Merchant Center',
									'google-listings-and-ads'
								) }
								info={ toAccountText( googleMCAccount.id ) }
								helperContent={ requiredText }
							/>
							{ hasAdsAccount && (
								<AccountSubsection
									title={ __(
										'Google Ads',
										'google-listings-and-ads'
									) }
									info={ toAccountText(
										googleAdsAccount.id
									) }
									helperContent={
										<Button
											isDestructive
											isLink
											onClick={
												openDisconnectAdsAccountModal
											}
										>
											{ __(
												'Disconnect Google Ads account only',
												'google-listings-and-ads'
											) }
										</Button>
									}
								/>
							) }
						</Section.Card.Body>
						<Section.Card.Footer>
							<Button
								isDestructive
								onClick={ openDisconnectAllAccountsModal }
							>
								{ __(
									'Disconnect all accounts',
									'google-listings-and-ads'
								) }
							</Button>
						</Section.Card.Footer>
					</>
				) }
			</Section.Card>
		</Section>
	);
}
