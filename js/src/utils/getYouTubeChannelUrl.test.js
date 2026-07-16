/**
 * Internal dependencies
 */
import getYouTubeChannelUrl from './getYouTubeChannelUrl';

describe( 'getYouTubeChannelUrl', () => {
	it( 'builds the public YouTube channel URL from the connected channel ID', () => {
		expect(
			getYouTubeChannelUrl( { id: 'UC1234567890abcdef' } )
		).toBe( 'https://www.youtube.com/channel/UC1234567890abcdef' );
	} );
} );
