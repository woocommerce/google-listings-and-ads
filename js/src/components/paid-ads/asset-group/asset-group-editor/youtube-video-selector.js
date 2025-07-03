/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppTooltip from '~/components/app-tooltip';
import AddAssetItemButton from './add-asset-item-button';
import MediaSelector from './media-selector';
import YouTubeVideoInputControl from './youtube-video-input-control';

/**
 * @typedef {Object} AssetVideoConfig
 * @property {string} title The title of the video.
 * @property {string} thumbnail The thumbnail URL of the video.
 * @property {string} url The URL of the video.
 */

/**
 * Renders a selector for YouTube videos.
 *
 * @param {Object} props React props.
 * @param {AssetVideoConfig[]} props.initialVideos The initial videos.
 * @param {number} [props.maxNumberOfVideos=5] The maximum number of videos. 5 by default.
 * @param {string} [props.reachedMaxNumberTip] The tooltip content floating on the add button when reaching the max number of videos.
 * @param {(videos: Array<AssetVideoConfig>) => void} props.onChange Callback function to be called when the videos are changed.
 */
export default function YoutubeVideoSelector( {
	initialVideos = [],
	maxNumberOfVideos = 5,
	reachedMaxNumberTip,
	onChange,
} ) {
	const [ videos, setVideos ] = useState( [ ...initialVideos ] );
	const [ showInputControl, setShowInputControl ] = useState( true );

	const handleAddYoutubeVideoClick = () => {
		setShowInputControl( true );
	};

	const renderAddButton = () => {
		const disabled = videos.length >= maxNumberOfVideos;
		const button = (
			<AddAssetItemButton
				disabled={ disabled }
				text={ __( 'Add YouTube video', 'google-listings-and-ads' ) }
				onClick={ handleAddYoutubeVideoClick }
			/>
		);

		if ( disabled ) {
			if ( reachedMaxNumberTip ) {
				return (
					<AppTooltip placement="top" text={ reachedMaxNumberTip }>
						{ button }
					</AppTooltip>
				);
			}

			return null;
		}

		return button;
	};

	const handleOnVideoAdded = ( videoDetails ) => {
		if ( videoDetails ) {
			if (
				! videos.some( ( video ) => video.url === videoDetails.url )
			) {
				const updatedVideos = [ ...videos, videoDetails ];
				setVideos( updatedVideos );
				onChange( updatedVideos );
			}

			setShowInputControl( false );
		}
	};

	const handleRemoveVideo = ( videoToRemove ) => {
		setVideos( ( prevVideos ) =>
			prevVideos.filter( ( video ) => video.url !== videoToRemove.url )
		);
	};

	const handleOnMediumClick = ( event, video = null ) => {
		if ( video?.url ) {
			window.open( video.url, '_blank', 'noopener,noreferrer' );
		}
	};

	return (
		<div className="gla-youtube-video-selector">
			<MediaSelector
				media={ videos }
				onRemoveMedia={ handleRemoveVideo }
				onMediumClick={ handleOnMediumClick }
				mediaAspectRatio="landscape"
				mediaType="video"
			/>

			{ showInputControl && (
				<YouTubeVideoInputControl onVideoAdded={ handleOnVideoAdded } />
			) }

			{ ! showInputControl && renderAddButton() }
		</div>
	);
}
