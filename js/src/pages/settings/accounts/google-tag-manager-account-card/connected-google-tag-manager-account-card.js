/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountCardTextDetail from '../account-card-text-detail';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from './constants';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useGoogleTagManagerContainers from './hooks/useGoogleTagManagerContainers';
import ConnectedIndicator from './connected-indicator';

/**
 * @typedef { import('~/data/types.js').GoogleTagManagerAccount } GoogleTagManagerAccount
 */

/**
 * Renders the connected Google Tag Manager account card: a "Connected" badge, an actions menu
 * offering "Open Google Tag Manager", and the connected account/container detail text — the
 * account name with its ID linking out to that account in Google Tag Manager itself, and the
 * container name/public ID as plain text below it. The connection record itself is a flat
 * identity+status record with no display fields, so the account/container display data is
 * looked up from `getExistingGoogleTagManagerAccounts`/`getGoogleTagManagerContainers` by
 * matching `account.id`/`account.containerId` — independent resolvers from the connection's own,
 * so `detail`/`indicator` render `null` until they've resolved, same as `AccountCard` already
 * does elsewhere while its own content is loading. The green/red tag-injection status notices
 * shown alongside this card belong to the sibling snippet-injection feature, not this card —
 * kept deliberately minimal.
 *
 * @param {Object} props Component props.
 * @param {GoogleTagManagerAccount} props.account The connected Google Tag Manager connection record.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleTagManagerAccountCard = ( { account, onDisconnect } ) => {
	const { existingAccounts, hasFinishedResolution: hasResolvedAccounts } =
		useExistingGoogleTagManagerAccounts();
	const { containers, hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();

	const selectedAccount = existingAccounts?.find(
		( acc ) => acc.id === account.id
	);
	const container = containers?.find(
		( item ) => item.id === account.containerId
	);
	const hasResolved =
		hasResolvedAccounts &&
		hasResolvedContainers &&
		selectedAccount &&
		container;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				hasResolved ? (
					<AccountCardTextDetail>
						<div>
							{ createInterpolateElement(
								sprintf(
									/* translators: %1$s: account name, %2$s: account ID link */
									__(
										'%1$s %2$s',
										'google-listings-and-ads'
									),
									selectedAccount.name,
									'<link>' + selectedAccount.id + '</link>'
								),
								{
									link: (
										<ExternalLink
											href={
												selectedAccount.tagManagerUrl
											}
										/>
									),
								}
							) }
						</div>
						<div>
							{ `${ container.name } (${ container.publicId })` }
						</div>
					</AccountCardTextDetail>
				) : null
			}
			indicator={
				hasResolved ? (
					<ConnectedIndicator
						account={ selectedAccount }
						onDisconnect={ onDisconnect }
					/>
				) : null
			}
			expandedDetail
		/>
	);
};

export default ConnectedGoogleTagManagerAccountCard;
