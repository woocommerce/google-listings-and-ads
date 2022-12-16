/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import GridiconPlusSmall from 'gridicons/dist/plus-small';
import GridiconCrossCircle from 'gridicons/dist/cross-circle';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useCroppedImageSelector from '.~/hooks/useCroppedImageSelector';
import './images-selector.scss';

/**
 * @typedef {Object} AssetImageConfig
 * @property {number} minWidth The minimum width.
 * @property {number} minHeight The minimum height.
 * @property {number} suggestedWidth The suggested width.
 * @property {number} suggestedHeight The suggested height.
 */

/**
 * Renders a selector for asset images.
 *
 * @param {Object} props React props.
 * @param {AssetImageConfig} props.imageConfig The config of the asset image.
 * @param {string[]} props.initialImageUrls The initial image URLs.
 * @param {number} [props.maxNumberOfImages=0] The maximum number of images. 0 by default and it means unlimited number.
 */
export default function ImagesSelector( {
	imageConfig,
	initialImageUrls = [],
	maxNumberOfImages = 0,
} ) {
	const [ replacingImage, setReplacingImage ] = useState( null );
	const [ images, setImages ] = useState( () =>
		initialImageUrls.map( ( url ) => ( { url, alt: '' } ) )
	);

	const removeImage = ( deletedImage ) => {
		setImages( images.filter( ( { id } ) => id !== deletedImage.id ) );
	};

	const handle = useCroppedImageSelector( {
		...imageConfig,
		onDelete: removeImage,
		onSelect( image ) {
			const nextImages = [ ...images ];

			// Find if there is a duplicate image first.
			let index = nextImages.findIndex( ( { id } ) => id === image.id );

			if ( replacingImage ) {
				if ( index !== -1 && image.id !== replacingImage.id ) {
					// If the selected image already exists while replacing, it's considered a swap position.
					nextImages.splice( index, 1, { ...replacingImage } );
				}
				// If index gets -1 here, it means the image to be replaced has been deleted.
				index = nextImages.indexOf( replacingImage );
			}

			if ( index === -1 ) {
				nextImages.push( image );
			} else {
				nextImages.splice( index, 1, image );
			}

			setReplacingImage( null );
			setImages( nextImages );
		},
	} );

	const handleUpsertImageClick = ( event, image = null ) => {
		setReplacingImage( image );
		handle.openSelector( image?.id );
	};

	return (
		<div className="gla-images-selector">
			<div className="gla-images-selector__image-list">
				{ images.map( ( image ) => {
					return (
						<div
							key={ image.url }
							className="gla-images-selector__image-item"
						>
							<AppButton
								className="gla-images-selector__replace-image-button"
								onClick={ () =>
									handleUpsertImageClick( null, image )
								}
							>
								<img
									className="gla-images-selector__image"
									alt={ image.alt }
									src={ image.url }
								/>
							</AppButton>
							<AppButton
								className="gla-images-selector__remove-image-button"
								icon={ <GridiconCrossCircle /> }
								iconSize={ 20 }
								onClick={ () => removeImage( image ) }
							/>
						</div>
					);
				} ) }
			</div>
			<AppButton
				className="gla-images-selector__add-image-button"
				isLink
				disabled={
					maxNumberOfImages !== 0 &&
					images.length >= maxNumberOfImages
				}
				icon={ <GridiconPlusSmall /> }
				iconSize={ 16 }
				text={ __( 'Add image', 'google-listings-and-ads' ) }
				onClick={ handleUpsertImageClick }
			/>
		</div>
	);
}
