/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppTooltip from '~/components/app-tooltip';
import { GEN_AI_ASSET_TYPES } from '~/constants';
import useAdblockerImageProxy from '~/hooks/useAdblockerImageProxy';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import useCroppedImageSelector from '~/hooks/useCroppedImageSelector';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AssetItemActionButton, {
	ACTION_TYPES,
} from './asset-item-action-button';
import GenAIImagePicker from './gen-ai-image-picker';
import MediaSelector from './media-selector';

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
	onChange = noop,
} ) {
	const { values } = useAdaptiveFormContext();
	const updateImagesRef = useRef();
	const [ awaitingActionImage, setAwaitingActionImage ] = useState( null );
	const [ generateAssets, isGeneratingAssets ] = useCreateGenAIAssets();
	const { createNotice } = useDispatchCoreNotices();
	const [ getProxyUrl ] = useAdblockerImageProxy();
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
					thumbnail: getProxyUrl( img.url ),
				} ) ) }
				onMediumClick={ handleMediumClick }
				onRemoveMedia={ handleRemoveImage }
			/>

			<GenAIImagePicker
				assetKey={ assetKey }
				onAddSelectedImages={ handleOnAddSelectedImages }
			/>

			{ children }
			{ renderAddButton() }

			{ generateButtonText && (
				<AssetItemActionButton
					action={ ACTION_TYPES.GENERATE }
					text={ generateButtonText }
					onClick={ handleGenerateClick }
					loading={ isGeneratingAssets }
				/>
			) }
		</div>
	);
}
