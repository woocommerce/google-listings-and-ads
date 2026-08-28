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
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';
import ConnectedIndicator from './connected-indicator';

/**
 * @typedef { import('~/data/types.js').GoogleTagManagerConnection } GoogleTagManagerConnection
 */

/**
 * Renders the connected Google Tag Manager account card: a "Connected" badge, an actions menu
 * offering "Open Google Tag Manager", and the connected account/container detail text — the
 * account name with its ID linking out to that account in Google Tag Manager itself, and the
 * container name/public ID as plain text below it. Once connected, the connection record itself
 * carries this display data, so no other resolver is consulted here.
 *
 * @param {Object} props Component props.
 * @param {GoogleTagManagerConnection} props.account The connected Google Tag Manager connection record.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleTagManagerAccountCard = ( { account, onDisconnect } ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				<AccountCardTextDetail>
					<div>
						{ createInterpolateElement(
							sprintf(
								/* translators: %1$s: account name, %2$s: account ID link */
								__( '%1$s %2$s', 'google-listings-and-ads' ),
								account.name,
								'<link>' + account.id + '</link>'
							),
							{
								link: (
									<ExternalLink
										href={ getGoogleTagManagerAccountUrl(
											account.id
										) }
									/>
								),
							}
						) }
					</div>
					<div>
						{ `${ account.containerName } (${ account.containerPublicId })` }
					</div>
				</AccountCardTextDetail>
			}
			indicator={
				<ConnectedIndicator
					account={ account }
					onDisconnect={ onDisconnect }
				/>
			}
			expandedDetail
		/>
	);
};

export default ConnectedGoogleTagManagerAccountCard;
