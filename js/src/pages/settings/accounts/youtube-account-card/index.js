/**
 * Internal dependencies
 */
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import ConnectedYouTubeAccountCard from './connected-youtube-account-card';
import ConnectYouTubeAccountCard from './connect-youtube-account-card';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

/**
 * @typedef {Object} YouTubeChannel
 * @property {string} id Channel ID.
 * @property {string} label Channel label name.
 */

/**
 * @typedef {Object} YouTubeAccount
 * @property {'connected'|'disconnected'} status Connection status.
 * @property {YouTubeChannel} [channel] Selected channel when connected.
 */

/**
 * Renders the YouTube account card, either connected or connect state.
 * Shows a loading spinner while the account data is being fetched.
 */
const YouTubeAccountCard = () => {
	const { youTubeAccount } = useYouTubeAccount();
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	if ( ! hasGoogleMCConnection ) {
		return null;
	}

	if (
		[
			YOUTUBE_ACCOUNT_STATUS.CONNECTED,
			YOUTUBE_ACCOUNT_STATUS.INCOMPLETE,
		].includes( youTubeAccount?.status )
	) {
		return (
			<ConnectedYouTubeAccountCard youTubeAccount={ youTubeAccount } />
		);
	}

	return <ConnectYouTubeAccountCard />;
};

export default YouTubeAccountCard;
