/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCroppedImageSelector from '~/hooks/useCroppedImageSelector';
import AppTooltip from '~/components/app-tooltip';
import AddAssetItemButton from './add-asset-item-button';
import MediaSelector from './media-selector';
import GenAIImagePicker from './gen-ai-image-picker';

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
 * @param {string} props.assetKey The asset key.
 * @param {AssetImageConfig} props.imageConfig The config of the asset image.
 * @param {string[]} props.initialImageUrls The initial image URLs.
 * @param {number} [props.maxNumberOfImages=-1] The maximum number of images. -1 by default and it means unlimited number.
 * @param {string} [props.reachedMaxNumberTip] The tooltip content floating on the add button when reaching the max number of images.
 * @param {JSX.Element} [props.children] Content to be rendered above the add button.
 * @param {(urls: Array<string>) => void} [props.onChange] Callback function to be called when the texts are changed.
 */
export default function ImagesSelector( {
	assetKey,
	imageConfig,
	initialImageUrls = [],
	maxNumberOfImages = -1,
	reachedMaxNumberTip,
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

	const handleRemoveImage = ( deletedImage ) => {
		if ( deletedImage.id === awaitingActionImage?.id ) {
			setAwaitingActionImage( null );
		}
		updateImages( images.filter( ( { id } ) => id !== deletedImage.id ) );
	};

	useEffect( () => {
		if ( maxNumberOfImages > -1 && images.length > maxNumberOfImages ) {
			updateImagesRef.current( images.slice( 0, maxNumberOfImages ) );
		}
	}, [ images, maxNumberOfImages ] );

	const handle = useCroppedImageSelector( {
		...imageConfig,
		onDelete: handleRemoveImage,
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

	const handleMediumClick = ( event, image = null ) => {
		setAwaitingActionImage( image );
		handle.openSelector( image?.id );
	};

	const handleOnAddSelectedImages = ( selectedImageUrls ) => {
		const selectedImages = selectedImageUrls.map( ( url ) => ( {
			url,
			id: url,
			alt: '',
		} ) );
		updateImages( [ ...images, ...selectedImages ] );
	};

	const renderAddButton = () => {
		const disabled =
			maxNumberOfImages !== -1 && images.length >= maxNumberOfImages;
		const button = (
			<AddAssetItemButton
				disabled={ disabled }
				text={ __( 'Add image', 'google-listings-and-ads' ) }
				onClick={ handleMediumClick }
			/>
		);

		if ( disabled && reachedMaxNumberTip ) {
			return (
				<AppTooltip placement="top" text={ reachedMaxNumberTip }>
					{ button }
				</AppTooltip>
			);
		}

		return button;
	};

	return (
		<div className="gla-images-selector">
			<MediaSelector
				media={ images }
				onMediumClick={ handleMediumClick }
				onRemoveMedia={ handleRemoveImage }
			/>

			<GenAIImagePicker
				assetKey={ assetKey }
				onAddSelectedImages={ handleOnAddSelectedImages }
				images={ [
					'https://picsum.photos/200',
					'https://picsum.photos/210',
					'https://picsum.photos/220',
				] }
			/>

			{ children }
			{ renderAddButton() }
		</div>
	);
}
