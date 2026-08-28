/**
 * Internal dependencies
 */
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import ConnectedYouTubeAccountCard from './connected-youtube-account-card';
import ConnectYouTubeAccountCard from './connect-youtube-account-card';

/**
 * @typedef {Object} YouTubeChannel
 * @property {string} id Channel ID.
 * @property {string} label Channel label name.
 */

/**
 * @typedef {Object} YouTubeAccount
 * @property {'connected'|'disconnected'|'incomplete'} status Connection status.
 * @property {YouTubeChannel} [channel] Selected channel when connected.
 */

/**
 * Renders the YouTube account card, either connected or connect state.
 * Shows a loading spinner while the account data is being fetched.
 * @param {Object} props Component props.
 * @param {boolean} [props.disabled=false] Whether connecting YouTube is disabled.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the YouTube account.
 * @return {JSX.Element} The YouTube account card.
 */
const YouTubeAccountCard = ( { disabled = false, onDisconnect } ) => {
	const { youTubeAccount } = useYouTubeAccount();

	if (
		[
			YOUTUBE_ACCOUNT_STATUS.CONNECTED,
			YOUTUBE_ACCOUNT_STATUS.INCOMPLETE,
		].includes( youTubeAccount?.status )
	) {
		return (
			<ConnectedYouTubeAccountCard
				youTubeAccount={ youTubeAccount }
				onDisconnect={ onDisconnect }
			/>
		);
	}

	return <ConnectYouTubeAccountCard disabled={ disabled } />;
};

export default YouTubeAccountCard;
