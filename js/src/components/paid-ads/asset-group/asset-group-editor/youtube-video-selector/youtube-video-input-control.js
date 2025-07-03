/**
 * External dependencies
 */
import classNames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppInputControl from '~/components/app-input-control';
import AppButton from '~/components/app-button';
import './youtube-video-input-control.scss';

/**
 * Checks if a given URL is a valid YouTube video URL.
 *
 * Accepts both standard (youtube.com/watch?v=) and shortened (youtu.be/) formats.
 *
 * @param {string} url - The URL to validate.
 * @return {boolean} True if the URL is a valid YouTube video URL, false otherwise.
 */
const isValidYouTubeUrl = ( url ) => {
	const youtubeUrlPattern =
		/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[\w-]{11}(&.*)?$/;
	if ( ! youtubeUrlPattern.test( url.trim() ) ) {
		return false;
	}

	return true;
};

const YouTubeVideoInputControl = ( { onVideoAdded } ) => {
	const [ url, setUrl ] = useState( '' );
	const [ error, setError ] = useState( null );
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		if ( url === '' ) {
			setError( null );
		}
	}, [ url ] );

	const handleOnClick = async () => {
		setError( null );
		setLoading( true );

		try {
			if ( ! isValidYouTubeUrl( url ) ) {
				setError(
					__( 'Invalid YouTube URL', 'google-listings-and-ads' )
				);
				return;
			}

			const response = await fetch(
				`https://www.youtube.com/oembed?format=json&url=${ encodeURIComponent(
					url
				) }`
			);
			if ( ! response.ok ) {
				setError(
					__(
						'Failed to fetch video details',
						'google-listings-and-ads'
					)
				);
				return;
			}
			const data = await response.json();
			const videoDetails = {
				title: data.title,
				thumbnail: data.thumbnail_url,
				url,
			};

			onVideoAdded( videoDetails );
			setUrl( '' );
		} catch ( e ) {
			setError(
				sprintf(
					/* translators: %s is the error message */
					__(
						'Failed to fetch video details. Please check the URL and try again. Error: %s',
						'google-listings-and-ads'
					),
					e.message
				)
			);
		} finally {
			setLoading( false );
		}
	};

	return (
		<div className="gla-youtube-video-input-control">
			<p className="gla-youtube-video-input-control__label">
				{ __( 'Select YouTube video URL', 'google-listings-and-ads' ) }
			</p>
			<Flex align="flex-start">
				<FlexBlock>
					<AppInputControl
						value={ url }
						onChange={ setUrl }
						help={ error }
						className={ classNames( {
							'has-error': error,
						} ) }
					/>
				</FlexBlock>
				<FlexItem>
					<AppButton
						onClick={ handleOnClick }
						variant="secondary"
						disabled={ ! url }
						loading={ loading }
					>
						{ __( 'Add Video', 'google-listings-and-ads' ) }
					</AppButton>
				</FlexItem>
			</Flex>
		</div>
	);
};

export default YouTubeVideoInputControl;
