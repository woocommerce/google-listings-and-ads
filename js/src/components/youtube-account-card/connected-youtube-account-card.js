/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';
import Section from '~/components/section';
import DisconnectAccount from './disconnect-account';

/**
 * @typedef { import('./youtube-account-card.js').YouTubeAccount } YouTubeAccount
 */

/**
 * Component to display a connected YouTube account.
 *
 * @param {Object} props
 * @param {YouTubeAccount} props.youTubeAccount The connected YouTube account.
 */
const ConnectedYouTubeAccountCard = ( { youTubeAccount } ) => {
	let accountCardProps = {};
	if ( youTubeAccount?.channel?.id ) {
		accountCardProps = {
			description: youTubeAccount.channel.label,
			indicator: <ConnectedIconLabel />,
		};
	} else {
		accountCardProps = {
			actions: (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'No channels found (or permission not granted).',
						'google-listings-and-ads'
					) }
				</Notice>
			),
		};
	}

	return (
		<AccountCard appearance={ APPEARANCE.YOUTUBE } { ...accountCardProps }>
			<Section.Card.Footer>
				<DisconnectAccount />
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectedYouTubeAccountCard;
