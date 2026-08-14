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
import useAdminUrl from '~/hooks/useAdminUrl';
import AppButton from '~/components/app-button';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';
import { queueRecordGlaEvent } from '~/utils/tracks';
import DisconnectModal, { ALL_ACCOUNTS } from '../disconnect-modal';
import useConnectedAccounts, { ACCOUNT_SECTION } from './useConnectedAccounts';
import WPComAccountCard from './wpcom-account-card';
import GoogleAccountCard from './google-account-card';
import GoogleMerchantCenterAccountCard from './merchant-center-account-card';
import GoogleAdsAccountCard from './google-ads-account-card';
import YouTubeAccountCard from './youtube-account-card';
import AccountsGroup from './accounts-group';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import './index.scss';

const SECTIONS = [
	{
		key: ACCOUNT_SECTION.REQUIRED,
		title: __( 'Required', 'google-listings-and-ads' ),
		description: __(
			'The extension needs these to run.',
			'google-listings-and-ads'
		),
	},
	{
		key: ACCOUNT_SECTION.GROW,
		title: __( 'Grow your reach', 'google-listings-and-ads' ),
		description: __(
			'Optional. Connect more Google services to your store.',
			'google-listings-and-ads'
		),
	},
];

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
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const { isLoading } = useConnectedAccounts();

	// Which disconnect modal is open, keyed by the disconnect target.
	const [ openedModal, setOpenedModal ] = useState( null );
	const handleRequestClose = () => setOpenedModal( null );
	const handleDisconnectAll = () => setOpenedModal( ALL_ACCOUNTS );

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
					<YouTubeAccountCard />
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
