/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconCrossCircle from 'gridicons/dist/cross-circle';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import youtubeIconURL from '~/images/pmax-assets-improvements/youtube-icon.svg';
import './media-selector.scss';

const ARIA_LABEL_CLICK = {
	image: __( 'Replace image', 'google-listings-and-ads' ),
	video: __( 'View video', 'google-listings-and-ads' ),
};

/**
 * @typedef {Object} Media
 * @property {string} url The URL of the media.
 * @property {string} alt The alt text for the media.
 * @property {string} [thumbnail] The thumbnail URL of the media.
 */

/**
 * Renders a media selector component that displays a list of media items
 * and allows users to click on them or remove them.
 *
 * @param {Object} props The component props.
 * @param {Array<Media>} props.media The list of media items to display.
 * @param {Function} props.onMediumClick Callback function when a medium is clicked.
 * @param {Function} props.onRemoveMedia Callback function when a medium is removed.
 * @param {'video'|'image'} [props.mediaType='image'] The type of media being displayed (default is 'image').
 */
const MediaSelector = ( {
	media = [],
	onMediumClick,
	onRemoveMedia,
	mediaType = 'image',
} ) => {
	if ( ! media.length ) {
		return null;
	}

	return (
		<div
			className={ `gla-media-selector gla-media-selector--has-${ mediaType }-media-type` }
		>
			<div className="gla-media-selector__medium-list">
				{ media.map( ( medium ) => {
					return (
						<div
							className="gla-media-selector__item"
							key={ medium.url }
						>
							<AppButton
								aria-label={ ARIA_LABEL_CLICK[ mediaType ] }
								className="gla-media-selector__medium-button"
								onClick={ () => onMediumClick( null, medium ) }
							>
								<img
									alt={ medium.alt }
									className="gla-media-selector__medium"
									src={ medium.thumbnail || medium.url }
								/>

								{ mediaType === 'video' && (
									<img
										alt={ __(
											'YouTube icon',
											'google-listings-and-ads'
										) }
										className="gla-media-selector__youtube-icon"
										height={ 24 }
										src={ youtubeIconURL }
										width={ 32 }
									/>
								) }
							</AppButton>
							<AppButton
								aria-label={ __(
									'Remove media',
									'google-listings-and-ads'
								) }
								className="gla-media-selector__remove-medium-button"
								icon={ <GridiconCrossCircle /> }
								iconSize={ 20 }
								onClick={ () => onRemoveMedia( medium ) }
							/>
						</div>
					);
				} ) }
			</div>
		</div>
	);
};

export default MediaSelector;
