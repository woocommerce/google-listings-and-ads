/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useJetpackAccount from '.~/hooks/useJetpackAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import DisconnectSection from './disconnect-section';
import AccountSubsection from './account-subsection';
import { ALL_ACCOUNTS, ADS_ACCOUNT } from './constants';

const withPrefix = ( id ) => {
	return createInterpolateElement(
		__( 'Account <id />', 'google-listings-and-ads' ),
		{
			id: <>{ id }</>,
		}
	);
};

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
	const hasAdsAccount = googleAdsAccount?.status === 'connected';

	const [ openedModal, setOpenedModal ] = useState( null );
	const openDisconnectAllAccountsModal = () => setOpenedModal( ALL_ACCOUNTS );
	const openDisconnectAdsAccountModal = () => setOpenedModal( ADS_ACCOUNT );
	const dismissModal = () => setOpenedModal( null );

	if ( openedModal ) {
		// TODO: disconnect modal will be implemented by another PR.
		return <Button onClick={ dismissModal }>Back</Button>;
	}

	return (
		<DisconnectSection isLoading={ isLoading }>
			<Section.Card.Body>
				<AccountSubsection
					title={ __( 'WordPress.com', 'google-listings-and-ads' ) }
					info={ jetpack?.email }
				/>
				<AccountSubsection
					title={ __( 'Google', 'google-listings-and-ads' ) }
					info={ google?.email }
				/>
				<AccountSubsection
					title={ __(
						'Google Merchant Center',
						'google-listings-and-ads'
					) }
					info={ withPrefix( googleMCAccount?.id ) }
				/>
				{ hasAdsAccount && (
					<AccountSubsection
						title={ __( 'Google Ads', 'google-listings-and-ads' ) }
						info={ withPrefix( googleAdsAccount?.id ) }
					>
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
					</AccountSubsection>
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
		</DisconnectSection>
	);
}
