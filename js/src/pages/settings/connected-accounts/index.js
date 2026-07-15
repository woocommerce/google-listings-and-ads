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
import SpinnerCard from '~/components/spinner-card';
import { queueRecordGlaEvent } from '~/utils/tracks';
import DisconnectModal, {
	ALL_ACCOUNTS,
	ADS_ACCOUNT,
} from '../disconnect-modal';
import useConnectedAccounts, { ACCOUNT_SECTION } from './useConnectedAccounts';
import AccountsGroupCard from './accounts-group-card';
import './index.scss';

const SECTIONS = [
	{
		key: ACCOUNT_SECTION.REQUIRED,
		title: __( 'Required', 'google-listings-and-ads' ),
		description: __(
			'Connected for you during setup. The extension needs these to run.',
			'google-listings-and-ads'
		),
	},
	{
		key: ACCOUNT_SECTION.GROW,
		title: __( 'Grow your reach', 'google-listings-and-ads' ),
		description: __(
			'Optional. Put your store in front of more shoppers, on Google Maps, Search, and YouTube.',
			'google-listings-and-ads'
		),
	},
];

/**
 * Accounts are disconnected from the Settings > Accounts subtab.
 *
 * @event gla_disconnected_accounts
 * @property {string} context (`all-accounts`|`ads-account`) - indicate which accounts have been disconnected.
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

	if ( isLoading ) {
		return <SpinnerCard />;
	}

	return (
		<div className="gla-connected-accounts">
			{ openedModal && (
				<DisconnectModal
					disconnectTarget={ openedModal }
					onRequestClose={ () => setOpenedModal( null ) }
					onDisconnected={ handleDisconnected }
				/>
			) }

			{ SECTIONS.map( ( section ) => {
				const sectionAccounts = accounts.filter(
					( account ) =>
						account.section === section.key &&
						// Show a row only when the account is connected or it
						// offers a connect action here (e.g. YouTube). This
						// matches the previous UI, which omitted a card entirely
						// when the account was disconnected and had no in-page
						// connect flow (e.g. Google Ads).
						( account.connected || account.canConnect )
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
						onDisconnect={ () => setOpenedModal( ADS_ACCOUNT ) }
					/>
				);
			} ) }

			<Flex justify="flex-end">
				<AppButton
					isPrimary
					isDestructive
					onClick={ () => setOpenedModal( ALL_ACCOUNTS ) }
				>
					{ __(
						'Disconnect from all accounts',
						'google-listings-and-ads'
					) }
				</AppButton>
			</Flex>
		</div>
	);
}
