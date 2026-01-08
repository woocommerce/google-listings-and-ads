/**
 * Internal dependencies
 */
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import AccountCard from '~/components/account-card';
import AppSpinner from '~/components/app-spinner';
import ConnectedYouTubeAccountCard from './connected-youtube-account-card';
import ConnectYouTubeAccountCard from './connect-youtube-account-card';

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
	const { youTubeAccount, hasFinishedResolution } = useYouTubeAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	if ( youTubeAccount?.status === YOUTUBE_ACCOUNT_STATUS.CONNECTED ) {
		return (
			<ConnectedYouTubeAccountCard youTubeAccount={ youTubeAccount } />
		);
	}

	return <ConnectYouTubeAccountCard />;
};

export default YouTubeAccountCard;
