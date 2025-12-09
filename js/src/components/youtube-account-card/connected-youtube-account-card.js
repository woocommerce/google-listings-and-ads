/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { getQuery, getHistory } from '@woocommerce/navigation';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';
import Section from '~/components/section';
import DisconnectAccount from './disconnect-account';
import useYouTubeSetupCompleteCallback from '~/hooks/useYouTubeSetupCompleteCallback';
import { getSettingsUrl } from '~/utils/urls';

/**
 * @typedef { import('./youtube-account-card.js').YouTubeAccount } YouTubeAccount
 */

/**
 * Component to display a connected YouTube account.
 * Detects if setup completion is needed via URL query (youtube=connected) and triggers it.
 *
 * @param {Object} props
 * @param {YouTubeAccount} props.youTubeAccount The connected YouTube account.
 */
const ConnectedYouTubeAccountCard = ( { youTubeAccount } ) => {
	const youTubeConnected = getQuery()?.youtube === 'connected';
	const [ handleFinishSetup ] = useYouTubeSetupCompleteCallback();
	const hasCompletedSetupRef = useRef( false );

	useEffect( () => {
		async function completeSetup() {
			hasCompletedSetupRef.current = true;
			await handleFinishSetup();
			getHistory().replace( getSettingsUrl() );
		}

		if ( youTubeConnected && ! hasCompletedSetupRef.current ) {
			completeSetup();
		}
	}, [ youTubeConnected, handleFinishSetup ] );

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
