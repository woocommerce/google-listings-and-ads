/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Flex,
	FlexItem,
	CheckboxControl,
	FlexBlock,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useGenAIMediaAssets from '~/hooks/useGenAIMediaAssets';
import AppButton from '~/components/app-button';
import AIIcon from '~/images/ai-icon.svg?inline';
import './index.scss';

/**
 * Triggered when the "Add selected images" button is clicked.
 *
 * @event gla_gen_ai_image_picker_add_selected_images_click
 * @property {string} final_url The final URL for which the images were generated.
 * @property {string} asset_key The asset key for which the images were generated.
 * @property {number} num_selected_images The number of images that were selected to be added.
 */

/**
 * GenAIImagePicker component.
 * Allows users to pick AI-generated images based on the final URL and the spec type.
 *
 * @fires gla_gen_ai_image_picker_add_selected_images_click when the "Add selected images" button is clicked.
 *
 * @param {Object} props Component props.
 * @param {string} props.assetKey Asset key.
 * @param {(url: string) => string} props.getDisplayImageUrl Function to get the display URL for an image, useful for handling ad blockers.
 * @param {Function} props.onAddSelectedImages Callback to add selected images.
 */
export default function GenAIImagePicker( {
	assetKey,
	getDisplayImageUrl,
	onAddSelectedImages,
} ) {
	const { values } = useAdaptiveFormContext();
	const addedImageUrls = values[ assetKey ] || [];
	const { final_url: finalUrl } = values;
	const { assets } = useGenAIMediaAssets( finalUrl, assetKey );
	const [ selectedImages, setSelectedImages ] = useState( [] );

	const handleOnAddSelectedImages = () => {
		onAddSelectedImages( selectedImages );
		setSelectedImages( [] );
	};

	const toggleImageSelection = ( src ) => {
		setSelectedImages( ( previousImages ) =>
			previousImages.includes( src )
				? previousImages.filter( ( image ) => image !== src )
				: [ ...previousImages, src ]
		);
	};

	if ( ! assets || assets.length === 0 || ! finalUrl ) {
		return null;
	}

	return (
		<Flex className="gla-gen-ai-image-picker" direction="column" gap={ 4 }>
			<FlexBlock>
				<h3 className="gla-gen-ai-image-picker__title">
					<AIIcon height={ 24 } width={ 24 } />

					{ __( 'AI-generated images', 'google-listings-and-ads' ) }
				</h3>
				<p className="gla-gen-ai-image-picker__description">
					{ __(
						'Select to add these images to this set for your product.',
						'google-listings-and-ads'
					) }
				</p>
			</FlexBlock>

			<FlexBlock>
				<Flex
					className="gla-gen-ai-image-picker__images"
					gap={ 4 }
					justify="start"
					wrap
				>
					{ assets.map( ( src ) => {
						// Hide the image if it's already been added to the asset group.
						if ( addedImageUrls.includes( src ) ) {
							return null;
						}

						return (
							<FlexItem
								className="gla-gen-ai-image-picker__image"
								key={ src }
							>
								<AppButton
									aria-label={ __(
										'Select this image',
										'google-listings-and-ads'
									) }
									className="gla-gen-ai-image-picker__medium-button"
									onClick={ () =>
										toggleImageSelection( src )
									}
								>
									<img
										alt=""
										className="gla-media-selector__medium"
										src={ getDisplayImageUrl( src ) }
									/>
								</AppButton>

								<CheckboxControl
									checked={ selectedImages.includes( src ) }
									className="gla-gen-ai-image-picker__checkbox"
									onChange={ () =>
										toggleImageSelection( src )
									}
								/>
							</FlexItem>
						);
					} ) }
				</Flex>
			</FlexBlock>

			<FlexBlock>
				<AppButton
					disabled={ selectedImages.length === 0 }
					eventName="gla_gen_ai_image_picker_add_selected_images_click"
					eventProps={ {
						final_url: finalUrl,
						asset_key: assetKey,
						num_selected_images: selectedImages.length,
					} }
					onClick={ handleOnAddSelectedImages }
					text={ __(
						'Add selected images',
						'google-listings-and-ads'
					) }
					variant="secondary"
				/>
			</FlexBlock>
		</Flex>
	);
}
