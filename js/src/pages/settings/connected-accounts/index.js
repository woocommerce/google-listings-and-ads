/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
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
import AccountsGroupCard from './accounts-group-card';
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
	{
		key: ACCOUNT_SECTION.TRACKING,
		title: __( 'Tracking and Site tools', 'google-listings-and-ads' ),
		description: __(
			'Optional. Measure your traffic and manage how your store is tagged and indexed.',
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
export default function ConnectedAccounts() {
	const adminUrl = useAdminUrl();
	const { accounts, isLoading } = useConnectedAccounts();

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
			<Section className="gla-connected-accounts">
				<SpinnerCard />
			</Section>
		);
	}

	return (
		<Section className="gla-connected-accounts">
			{ openedModal && (
				<DisconnectModal
					disconnectTarget={ openedModal }
					onRequestClose={ handleRequestClose }
					onDisconnected={ handleDisconnected }
				/>
			) }

			{ SECTIONS.map( ( section ) => {
				const sectionAccounts = accounts.filter(
					( account ) =>
						account.section === section.key &&
						account.isVisible !== false &&
						// Show rows only for connected accounts, accounts that
						// offer an in-page connect action, or accounts with a
						// specialized row for an in-between state (e.g. an
						// incomplete connect flow).
						( account.connected ||
							account.ConnectComponent ||
							account.RowComponent )
				);

				if ( sectionAccounts.length === 0 ) {
					return null;
				}

				return (
					<AccountsGroupCard
						key={ section.key }
						title={ section.title }
						description={ section.description }
						accounts={ sectionAccounts }
						onDisconnect={ setOpenedModal }
					/>
				);
			} ) }

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
