/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GEN_AI_ASSET_TYPES } from '~/constants';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import useCroppedImageSelector from '~/hooks/useCroppedImageSelector';
import AppTooltip from '~/components/app-tooltip';
import AssetItemActionButton, {
	ACTION_TYPES,
} from './asset-item-action-button';
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
 * @param {string} props.generateButtonText The text for the generate button.
 * @param {number} [props.maxNumberOfImages=-1] The maximum number of images. -1 by default and it means unlimited number.
 * @param {string} [props.reachedMaxNumberTip] The tooltip content floating on the add button when reaching the max number of images.
 * @param {JSX.Element} [props.children] Content to be rendered above the add button.
 * @param {(url: string) => string} [props.getDisplayImageUrl] Function to get the display URL for an image, useful for handling ad blockers.
 * @param {(urls: Array<string>) => void} [props.onChange] Callback function to be called when the texts are changed.
 */
export default function ImagesSelector( {
	assetKey,
	imageConfig,
	initialImageUrls = [],
	generateButtonText,
	maxNumberOfImages = -1,
	reachedMaxNumberTip,
	children,
	getDisplayImageUrl = ( url ) => url,
	onChange = noop,
} ) {
	const { values } = useAdaptiveFormContext();
	const updateImagesRef = useRef();
	const [ awaitingActionImage, setAwaitingActionImage ] = useState( null );
	const { generateAssets, isGeneratingAssets } = useCreateGenAIAssets();
	const { createNotice } = useDispatchCoreNotices();
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
			const selectedIndex = nextImages.findIndex(
				( { id } ) => id === image.id
			);

			if ( awaitingActionImage ) {
				const awaitingIndex = nextImages.findIndex(
					( { id } ) => id === awaitingActionImage.id
				);

				if ( selectedIndex !== -1 && selectedIndex !== awaitingIndex ) {
					// Swap positions
					nextImages[ selectedIndex ] = awaitingActionImage;
					nextImages[ awaitingIndex ] = image;
				} else if ( awaitingIndex !== -1 ) {
					// Replace
					nextImages[ awaitingIndex ] = image;
				} else {
					// Previously clicked image no longer exists, push
					nextImages.push( image );
				}

				setAwaitingActionImage( null );
				updateImages( nextImages );
				return;
			}

			// Normal add flow (not replacing)
			if ( selectedIndex === -1 ) {
				nextImages.push( image );
			}

			updateImages( nextImages );
		},
	} );

	const handleMediumClick = ( event, image = null ) => {
		setAwaitingActionImage( image );
		handle.openSelector( image?.id );
	};

	const handleOnAddSelectedImages = ( selectedImageUrls ) => {
		const nextImages = [ ...images ];
		const selectedImages = selectedImageUrls
			.filter(
				( url ) => ! nextImages.some( ( img ) => img?.url === url )
			)
			.map( ( url ) => ( {
				url,
				id: url,
				alt: '',
			} ) );

		updateImages( [ ...nextImages, ...selectedImages ] );
	};

	const renderAddButton = () => {
		const disabled =
			maxNumberOfImages !== -1 && images.length >= maxNumberOfImages;
		const button = (
			<AssetItemActionButton
				disabled={ disabled }
				onClick={ handleMediumClick }
				text={ __( 'Add image', 'google-listings-and-ads' ) }
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

	const handleGenerateClick = async () => {
		try {
			const { final_url: finalUrl } = values;
			await generateAssets( finalUrl, [
				{ type: GEN_AI_ASSET_TYPES.MEDIA, assetKey },
			] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Something went wrong while generating images. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<div className="gla-images-selector">
			<MediaSelector
				media={ images.map( ( img ) => ( {
					...img,
					thumbnail: getDisplayImageUrl( img.url ),
				} ) ) }
				onMediumClick={ handleMediumClick }
				onRemoveMedia={ handleRemoveImage }
			/>

			<GenAIImagePicker
				assetKey={ assetKey }
				getDisplayImageUrl={ getDisplayImageUrl }
				onAddSelectedImages={ handleOnAddSelectedImages }
			/>

			{ children }
			{ renderAddButton() }

			{ generateButtonText && (
				<AssetItemActionButton
					action={ ACTION_TYPES.GENERATE }
					loading={ isGeneratingAssets }
					onClick={ handleGenerateClick }
					text={ generateButtonText }
				/>
			) }
		</div>
	);
}
