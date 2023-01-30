/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState, useEffect, useRef } from '@wordpress/element';
import GridiconCrossCircle from 'gridicons/dist/cross-circle';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useCroppedImageSelector from '.~/hooks/useCroppedImageSelector';
import AddAssetItemButton from './add-asset-item-button';
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
 * @param {JSX.Element} [props.children] Content to be rendered above the add button.
 * @param {(urls: Array<string>) => void} [props.onChange] Callback function to be called when the texts are changed.
 */
export default function ImagesSelector( {
	imageConfig,
	initialImageUrls = [],
	maxNumberOfImages = 0,
	children,
	onChange = noop,
} ) {
	const updateImagesRef = useRef();
	const [ awaitingActionImage, setAwaitingActionImage ] = useState( null );
	const [ images, setImages ] = useState( () =>
		// The asset images fetched from Google Ads are only URLs.
		initialImageUrls.map( ( url ) => ( { url, id: url, alt: '' } ) )
	);

	const updateImages = ( nextImages ) => {
		setImages( nextImages );
		onChange( nextImages.map( ( image ) => image.url ) );
	};
	updateImagesRef.current = updateImages;

	const removeImage = ( deletedImage ) => {
		if ( deletedImage.id === awaitingActionImage?.id ) {
			setAwaitingActionImage( null );
		}
		updateImages( images.filter( ( { id } ) => id !== deletedImage.id ) );
	};

	useEffect( () => {
		if ( maxNumberOfImages > 0 && images.length > maxNumberOfImages ) {
			updateImagesRef.current( images.slice( 0, maxNumberOfImages ) );
		}
	}, [ images, maxNumberOfImages ] );

	const handle = useCroppedImageSelector( {
		...imageConfig,
		onDelete: removeImage,
		onSelect( image ) {
			const nextImages = [ ...images ];

			// Find if there is a duplicate image first.
			let index = nextImages.findIndex( ( { id } ) => id === image.id );

			if ( awaitingActionImage ) {
				if ( index !== -1 && image.id !== awaitingActionImage.id ) {
					// If the selected image already exists while replacing, it's considered a swap position.
					nextImages.splice( index, 1, { ...awaitingActionImage } );
				}
				// Find the index to be replaced with the selected image.
				index = nextImages.indexOf( awaitingActionImage );
			}

			if ( index === -1 ) {
				nextImages.push( image );
			} else {
				nextImages.splice( index, 1, image );
			}

			setAwaitingActionImage( null );
			updateImages( nextImages );
		},
	} );

	const handleUpsertImageClick = ( event, image = null ) => {
		setAwaitingActionImage( image );
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
								aria-label={ __(
									'Replace image',
									'google-listings-and-ads'
								) }
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
								aria-label={ __(
									'Remove image',
									'google-listings-and-ads'
								) }
								icon={ <GridiconCrossCircle /> }
								iconSize={ 20 }
								onClick={ () => removeImage( image ) }
							/>
						</div>
					);
				} ) }
			</div>
			{ children }
			<AddAssetItemButton
				disabled={
					maxNumberOfImages !== 0 &&
					images.length >= maxNumberOfImages
				}
				text={ __( 'Add image', 'google-listings-and-ads' ) }
				onClick={ handleUpsertImageClick }
			/>
		</div>
	);
}
