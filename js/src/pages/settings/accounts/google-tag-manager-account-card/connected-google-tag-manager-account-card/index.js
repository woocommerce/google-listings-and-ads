/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink, Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountCardTextDetail from '../../account-card-text-detail';
import AccountDetailRow from '../../account-detail-row';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from '../constants';
import AccountNameWithLink from '../components/account-name-with-link';
import useGoogleTagManagerContainerUrl from '~/hooks/useGoogleTagManagerContainerUrl';
import ConnectedIndicator from './connected-indicator';
import './index.scss';

/**
 * @typedef { import('~/data/types.js').GoogleTagManagerConnection } GoogleTagManagerConnection
 */

/**
 * Renders the connected Google Tag Manager account card: a "Connected" badge, an actions menu
 * offering "Open Google Tag Manager", the account name with its ID linking out to that account in
 * Google Tag Manager itself, and a row below it with the container name and public ID, linking out
 * to the container. Once connected, the connection record itself carries this display data, so no
 * other resolver is consulted here.
 *
 * @param {Object} props Component props.
 * @param {GoogleTagManagerConnection} props.account The connected Google Tag Manager connection record.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleTagManagerAccountCard = ( { account, onDisconnect } ) => {
	const containerUrl = useGoogleTagManagerContainerUrl( account );
	const containerText = `${ account.containerName } (${ account.containerPublicId })`;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				<Flex direction="column" gap={ 4 }>
					<AccountCardTextDetail>
						<div className="gla-google-tag-manager-connected-account-card__text-detail">
							<p>
								<AccountNameWithLink account={ account } />
							</p>
						</div>
					</AccountCardTextDetail>
					<AccountDetailRow
						label={ __( 'Container', 'google-listings-and-ads' ) }
					>
						{ containerUrl ? (
							<ExternalLink href={ containerUrl }>
								{ containerText }
							</ExternalLink>
						) : (
							<span>{ containerText }</span>
						) }
					</AccountDetailRow>
				</Flex>
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
