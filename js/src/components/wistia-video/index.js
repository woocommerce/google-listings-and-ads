/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import useScript from '.~/hooks/useScript';
import './index.scss';

const DEFAULT_IFRAME_PROPS = {
	allow: 'fullscreen',
	allowtransparency: 'true',
	scrolling: 'no',
	width: '100%',
	height: '100%',
	className: 'wistia_embed',
	name: 'wistia_embed',
};

const WISTIA_API_SCRIPT_URL = 'https://fast.wistia.com/assets/external/E-v1.js';

/**
 * Embed a Wistia video using <iframe>.
 *
 * The embedded video has the height of 270px at the beginning, it will be expanded
 * to the original video height after the video is being played.
 *
 * Uses Wistia's JavaScript Player API to bind the video's "play" event in order to expand the video.
 * https://wistia.com/support/developers/player-api#with-iframe-embeds
 * https://wistia.com/support/developers/player-api#bind-eventtype-callbackfn
 *
 * @param {Object} props               React props.
 * @param {string} props.id            The Wistia's video ID.
 * @param {string} props.src           The Wistia's embedded video url.
 * @param {string} props.title         The Wistia's embedded video title.
 * @param {Object} [props.iframeProps] The properties of <iframe>.
 */
const WistiaVideo = ( props ) => {
	const { id, src, title, iframeProps = {} } = props;
	const [ isPlaying, setPlaying ] = useState( false );

	useScript( WISTIA_API_SCRIPT_URL, () => {
		if ( window._wq ) {
			window._wq.push( {
				id,
				onReady: ( video ) => {
					video.bind( 'play', () => setPlaying( true ) );
				},
			} );
		}
	} );

	const getIframeTitle = () => {
		if ( ! title ) {
			return __( 'Wistia Video', 'google-listings-and-ads' );
		}
		return sprintf(
			// translators: %s: The title of the iframe.
			__( '%s Video', 'google-listings-and-ads' ),
			title
		);
	};

	return (
		<div
			className={ classNames(
				'wistia-responsive-padding',
				isPlaying ? 'is-playing' : ''
			) }
		>
			<div className="wistia-responsive-wrapper">
				<iframe
					src={ src }
					title={ getIframeTitle() }
					{ ...{ ...DEFAULT_IFRAME_PROPS, ...iframeProps } }
				/>
			</div>
		</div>
	);
};

export default WistiaVideo;
