/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, CardDivider } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getGetStartedUrl } from '~/utils/urls';
import { queueRecordGlaEvent } from '~/utils/tracks';
import AppButton from '~/components/app-button';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';
import WPComAccountCard from './wpcom-account-card';
import GoogleAccountCard from './google-account-card';
import GoogleMerchantCenterAccountCard from './merchant-center-account-card';
import GoogleAdsAccountCard from './google-ads-account-card';
import YouTubeAccountCard from './youtube-account-card';
import AccountsGroup from './accounts-group';
import useAdminUrl from '~/hooks/useAdminUrl';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import DisconnectModal, {
	ALL_ACCOUNTS,
	YOUTUBE_ACCOUNT,
} from '../disconnect-modal';
import './index.scss';

/**
 * Accounts are disconnected from the Settings > Accounts subtab.
 *
 * @event gla_disconnected_accounts
 * @property {string} context (`all-accounts`|`youtube-account`) - indicate which accounts have been disconnected.
 */

/**
 * Renders the accounts, grouped into single-card sections. Individual
 * disconnect is offered (via the row actions menu) only for accounts where it
 * is possible today. The "Disconnect from all accounts" button is kept as-is.
 *
 * @fires gla_disconnected_accounts
 * @return {JSX.Element} The connected accounts subtab content.
 */
export default function Accounts() {
	const adminUrl = useAdminUrl();
	const { hasFinishedResolution: hasResolvedJetpackAccount } =
		useJetpackAccount();
	const { hasFinishedResolution: hasResolvedGoogleAccount } =
		useGoogleAccount();
	const {
		hasGoogleMCConnection,
		hasFinishedResolution: hasResolvedMCAccount,
	} = useGoogleMCAccount();
	const { hasFinishedResolution: hasResolvedGoogleAdsAccount } =
		useGoogleAdsAccount();
	const { hasFinishedResolution: hasResolvedYouTubeAccount } =
		useYouTubeAccount();

	// Which disconnect modal is open, keyed by the disconnect target.
	const [ openedModal, setOpenedModal ] = useState( null );
	const handleRequestClose = () => setOpenedModal( null );
	const handleDisconnectAll = () => setOpenedModal( ALL_ACCOUNTS );

	const isLoading = ! (
		hasResolvedJetpackAccount &&
		hasResolvedGoogleAccount &&
		hasResolvedMCAccount &&
		hasResolvedGoogleAdsAccount &&
		hasResolvedYouTubeAccount
	);

	const handleDisconnected = () => {
		const disconnectedTarget = openedModal;

		queueRecordGlaEvent( 'gla_disconnected_accounts', {
			context: disconnectedTarget,
		} );

		if ( disconnectedTarget === ALL_ACCOUNTS ) {
			// Reload WC admin page to update the `glaData` initiated from the static script.
			window.location.href = adminUrl + getGetStartedUrl();
		}
	};

	const handleDisconnectYouTubeAccount = () => {
		setOpenedModal( YOUTUBE_ACCOUNT );
	};

	if ( isLoading ) {
		return (
			<Section className="gla-accounts">
				<SpinnerCard />
			</Section>
		);
	}

	return (
		<Section className="gla-accounts">
			{ openedModal && (
				<DisconnectModal
					disconnectTarget={ openedModal }
					onRequestClose={ handleRequestClose }
					onDisconnected={ handleDisconnected }
				/>
			) }

			<AccountsGroup
				title={ __( 'Required', 'google-listings-and-ads' ) }
				description={ __(
					'The extension needs these to run.',
					'google-listings-and-ads'
				) }
			>
				<WPComAccountCard />
				<CardDivider />
				<GoogleAccountCard />
				<CardDivider />
				<GoogleMerchantCenterAccountCard />
				<CardDivider />
				<GoogleAdsAccountCard />
			</AccountsGroup>

			{ hasGoogleMCConnection && (
				<AccountsGroup
					title={ __( 'Grow your reach', 'google-listings-and-ads' ) }
					description={ __(
						'Optional. Connect more Google services to your store.',
						'google-listings-and-ads'
					) }
				>
					<YouTubeAccountCard
						onDisconnect={ handleDisconnectYouTubeAccount }
					/>
				</AccountsGroup>
			) }

			<Flex justify="flex-end">
				<AppButton
					onClick={ handleDisconnectAll }
					isPrimary
					isDestructive
				>
					{ __(
						'Disconnect from all accounts',
						'google-listings-and-ads'
					) }
				</AppButton>
			</Flex>
		</Section>
	);
}
