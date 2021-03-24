/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

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
						<Button isDestructive isLink>
							{ __(
								'Disconnect Google Ads account only',
								'google-listings-and-ads'
							) }
						</Button>
					</AccountSubsection>
				) }
			</Section.Card.Body>
			<Section.Card.Footer>
				<Button isDestructive>
					{ __(
						'Disconnect all accounts',
						'google-listings-and-ads'
					) }
				</Button>
			</Section.Card.Footer>
		</DisconnectSection>
	);
}
