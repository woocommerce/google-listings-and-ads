const YOUTUBE_CHANNEL_BASE_URL = 'https://www.youtube.com/channel/';

/**
 * Build the public YouTube channel URL for a connected channel.
 *
 * @param {{ id?: string|null }} [channel] Connected YouTube channel data.
 * @return {string} YouTube channel URL.
 */
export default function getYouTubeChannelUrl( channel ) {
	if ( ! channel?.id ) {
		return YOUTUBE_CHANNEL_BASE_URL;
	}

	return `${ YOUTUBE_CHANNEL_BASE_URL }${ channel.id }`;
}
