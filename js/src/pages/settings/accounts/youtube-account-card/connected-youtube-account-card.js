/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, ExternalLink } from '@wordpress/components';
import { useReducedMotion } from '@wordpress/compose';
import { useEffect, useRef } from '@wordpress/element';
import { getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getYouTubeChannelUrl, getAccountsSettingsUrl } from '~/utils/urls';
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountCardTextDetail from '../account-card-text-detail';
import useYouTubeSetupCompleteCallback from '~/hooks/useYouTubeSetupCompleteCallback';
import Indicator from './indicator';

/**
 * @typedef { import('./index.js').YouTubeAccount } YouTubeAccount
 */

/**
 * Component to display a connected YouTube account.
 * Detects if setup completion is needed via URL query (youtube=connected) and triggers it.
 *
 * @param {Object} props
 * @param {YouTubeAccount} props.youTubeAccount The connected YouTube account.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the YouTube account.
 * @return {JSX.Element} The connected YouTube account card.
 */
const ConnectedYouTubeAccountCard = ( { youTubeAccount, onDisconnect } ) => {
	const isReducedMotion = useReducedMotion();
	const isYouTubeOAuthReturn = getQuery()?.youtube === 'connected';
	const hasCompletedSetupRef = useRef( false );
	const containerRef = useRef();
	const [ handleFinishSetup, { loading, error } ] =
		useYouTubeSetupCompleteCallback();
	const shouldLinkYouTubeAccount =
		youTubeAccount.status === YOUTUBE_ACCOUNT_STATUS.INCOMPLETE;

	useEffect( () => {
		async function completeSetup() {
			containerRef.current.scrollIntoView( {
				behavior: isReducedMotion ? 'auto' : 'smooth',
				inline: 'nearest',
				block: 'nearest',
			} );

			hasCompletedSetupRef.current = true;
			await handleFinishSetup();
			getHistory().replace( getAccountsSettingsUrl() );
		}

		if (
			isYouTubeOAuthReturn &&
			shouldLinkYouTubeAccount &&
			! hasCompletedSetupRef.current
		) {
			completeSetup();
		}
	}, [
		isYouTubeOAuthReturn,
		handleFinishSetup,
		shouldLinkYouTubeAccount,
		isReducedMotion,
	] );

	let accountCardProps = {
		detail: (
			<AccountCardTextDetail>
				<ExternalLink
					href={ getYouTubeChannelUrl( youTubeAccount.channel ) }
				>
					{ youTubeAccount.channel.label }
				</ExternalLink>
			</AccountCardTextDetail>
		),
	};

	if ( shouldLinkYouTubeAccount ) {
		accountCardProps = {
			detail: error?.message ? (
				<Notice status="error" isDismissible={ false }>
					{ error.message }
				</Notice>
			) : undefined,
			description: loading
				? __( 'Please wait…', 'google-listings-and-ads' )
				: __(
						'Your YouTube account is connected, but setup isn’t complete yet.',
						'google-listings-and-ads'
				  ),
		};
	}

	return (
		<div ref={ containerRef }>
			<AccountCard
				appearance={ APPEARANCE.YOUTUBE }
				description={ __(
					'List your products on YouTube and track sales from your videos.',
					'google-listings-and-ads'
				) }
				alignIcon="top"
				alignIndicator="top"
				indicator={
					<Indicator
						handleFinishSetup={ handleFinishSetup }
						isConnected={ ! shouldLinkYouTubeAccount }
						isLoading={ loading }
						onDisconnect={ onDisconnect }
					/>
				}
				expandedDetail
				{ ...accountCardProps }
			/>
		</div>
	);
};

export default ConnectedYouTubeAccountCard;
