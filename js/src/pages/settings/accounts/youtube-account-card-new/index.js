/**
 * Internal dependencies
 */
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import ConnectYoutubeAccountCard from './connect-youtube-account-card';
import ConnectedYouTubeAccountCard from './connected-youtube-account-card';

const YouTubeAccountCard = () => {
	const { youTubeAccount } = useYouTubeAccount();
	const youTubeStatus = youTubeAccount?.status;
	const isYouTubeConnected = [
		YOUTUBE_ACCOUNT_STATUS.CONNECTED,
		YOUTUBE_ACCOUNT_STATUS.INCOMPLETE,
	].includes( youTubeStatus );

	if ( ! isYouTubeConnected ) {
		return <ConnectYoutubeAccountCard />;
	}

	return <ConnectedYouTubeAccountCard />;
};

export default YouTubeAccountCard;
