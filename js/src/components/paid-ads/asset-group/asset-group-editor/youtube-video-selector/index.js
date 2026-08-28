/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppTooltip from '~/components/app-tooltip';
import AssetItemActionButton from '../asset-item-action-button';
import MediaSelector from '../media-selector';
import YouTubeVideoInputControl from './youtube-video-input-control';

/**
 * Renders a selector for YouTube videos.
 *
 * @param {Object} props React props.
 * @param {Array<string>} props.initialVideos The initial videos.
 * @param {number} [props.maxNumberOfVideos=5] The maximum number of videos. 5 by default.
 * @param {string} [props.reachedMaxNumberTip] The tooltip content floating on the add button when reaching the max number of videos.
 * @param {(videos: Array<string>) => void} props.onChange Callback function to be called when the videos IDs are changed.
 */
export default function YoutubeVideoSelector( {
	initialVideos = [],
	maxNumberOfVideos = 5,
	reachedMaxNumberTip,
	onChange,
} ) {
	const [ videoIds, setVideoIds ] = useState( [ ...initialVideos ] );
	const [ showInputControl, setShowInputControl ] = useState( true );

	const handleAddYoutubeVideoClick = () => {
		setShowInputControl( true );
	};

	const renderAddButton = () => {
		const disabled = videoIds.length >= maxNumberOfVideos;
		const button = (
			<AssetItemActionButton
				disabled={ disabled }
				onClick={ handleAddYoutubeVideoClick }
				text={ __( 'Add YouTube video', 'google-listings-and-ads' ) }
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

	const handleOnVideoAdded = ( videoId ) => {
		if ( ! videoId ) {
			return;
		}

		if ( videoIds.includes( videoId ) ) {
			setShowInputControl( false );
			return;
		}

		setVideoIds( ( previousVideoIds ) => {
			const updatedVideoIds = [ ...previousVideoIds, videoId ];
			onChange( updatedVideoIds );
			return updatedVideoIds;
		} );

		setShowInputControl( false );
	};

	const handleRemoveVideo = ( videoToRemove ) => {
		setVideoIds( ( prevVideos ) => {
			const updatedVideoIds = prevVideos.filter(
				( videoId ) => videoId !== videoToRemove.id
			);
			onChange( updatedVideoIds );
			return updatedVideoIds;
		} );
	};

	const handleOnMediumClick = ( event, video ) => {
		if ( video?.url ) {
			window.open( video.url, '_blank', 'noopener,noreferrer' );
		}
	};

	const videos = videoIds.map( ( videoId ) => ( {
		id: videoId,
		url: `https://youtube.com/v/${ videoId }`,
		thumbnail: `https://img.youtube.com/vi/${ videoId }/mqdefault.jpg`,
	} ) );

	return (
		<div className="gla-youtube-video-selector">
			<MediaSelector
				media={ videos }
				mediaAspectRatio="landscape"
				mediaType="video"
				onMediumClick={ handleOnMediumClick }
				onRemoveMedia={ handleRemoveVideo }
			/>

			{ showInputControl && (
				<YouTubeVideoInputControl onVideoAdded={ handleOnVideoAdded } />
			) }

			{ ! showInputControl && renderAddButton() }
		</div>
	);
}
