/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconCrossCircle from 'gridicons/dist/cross-circle';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import './media-selector.scss';

const ARIA_LABEL_CLICK = {
	image: __( 'Replace image', 'google-listings-and-ads' ),
	video: __( 'View video', 'google-listings-and-ads' ),
};

const MediaSelector = ( {
	media = [],
	onMediumClick,
	onRemoveMedia,
	mediaType = 'image',
	mediaAspectRatio = 'square',
} ) => {
	if ( ! media.length ) {
		return null;
	}

	return (
		<div
			className={ `gla-media-selector gla-media-selector--has-${ mediaAspectRatio }-aspect-ratio` }
		>
			<div className="gla-media-selector__medium-list">
				{ media.map( ( medium ) => {
					return (
						<div
							key={ medium.url }
							className="gla-media-selector__item"
						>
							<AppButton
								className="gla-media-selector__medium-button"
								aria-label={ ARIA_LABEL_CLICK[ mediaType ] }
								onClick={ () => onMediumClick( null, medium ) }
							>
								<img
									className="gla-media-selector__medium"
									alt={ medium.alt }
									src={ medium.thumbnail || medium.url }
								/>
							</AppButton>
							<AppButton
								className="gla-media-selector__remove-medium-button"
								aria-label={ __(
									'Remove media',
									'google-listings-and-ads'
								) }
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
