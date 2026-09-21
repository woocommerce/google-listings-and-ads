/**
 * External dependencies
 */
import { TextareaControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { useAppDispatch } from '~/data';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import './index.scss';

const MAX_PROMPT_LENGTH = 1500;

/**
 * Modal for editing a single GenAI-generated image via a prompt.
 * Regenerates the image in recontext mode, preserving the source image's aspect ratio,
 * and replaces it in place on success.
 *
 * @param {Object} props React props.
 * @param {string} props.finalUrl The final URL the source image was generated for.
 * @param {string} props.assetKey The asset key (e.g. `marketing_image`) the source image belongs to.
 * @param {string} props.sourceImageUrl The `temporary_image_url` of the image being edited.
 * @param {(url: string) => string} props.getDisplayImageUrl Function to get the display URL for an image, useful for handling ad blockers.
 * @param {Function} props.onReplaceImage Callback invoked with `(sourceImageUrl, newImageUrl)` when the image has been replaced in place.
 * @param {Function} props.onRequestClose Callback invoked when the modal should close.
 */
export default function EditImageModal( {
	finalUrl,
	assetKey,
	sourceImageUrl,
	getDisplayImageUrl,
	onReplaceImage,
	onRequestClose,
} ) {
	const [ prompt, setPrompt ] = useState( '' );
	const { generateAssets, isGeneratingAssets, abortGenerateAssets } =
		useCreateGenAIAssets();
	const { replaceGenAIMediaAsset } = useAppDispatch();

	const trimmedPrompt = prompt.trim();
	const isOverLimit = prompt.length > MAX_PROMPT_LENGTH;
	const isGenerateDisabled =
		! trimmedPrompt || isOverLimit || isGeneratingAssets;

	const handleCancel = () => {
		abortGenerateAssets();
		onRequestClose();
	};

	const handleGenerate = async () => {
		const result = await generateAssets( finalUrl, [
			{
				type: GEN_AI_ASSET_TYPES.MEDIA,
				assetKey,
				prompt: trimmedPrompt,
				sourceImageUrl,
			},
		] );

		if (
			! result ||
			result.erroredTypes?.includes( GEN_AI_ASSET_TYPES.MEDIA )
		) {
			return;
		}

		const [ newImageUrl ] =
			result[ GEN_AI_ASSET_TYPES.MEDIA ]?.[ assetKey ] ?? [];

		if ( ! newImageUrl ) {
			return;
		}

		replaceGenAIMediaAsset(
			finalUrl,
			assetKey,
			sourceImageUrl,
			newImageUrl
		);
		onReplaceImage( sourceImageUrl, newImageUrl );
		onRequestClose();
	};

	return (
		<AppModal
			className="gla-gen-ai-edit-image-modal"
			title={ __( 'Edit image', 'google-listings-and-ads' ) }
			onRequestClose={ handleCancel }
			buttons={ [
				<AppButton key="cancel" isSecondary onClick={ handleCancel }>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="generate"
					isPrimary
					loading={ isGeneratingAssets }
					disabled={ isGenerateDisabled }
					onClick={ handleGenerate }
				>
					{ __( 'Generate', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			<img
				className="gla-gen-ai-edit-image-modal__thumbnail"
				src={ getDisplayImageUrl( sourceImageUrl ) }
				alt=""
			/>

			<TextareaControl
				label={ __( 'Prompt', 'google-listings-and-ads' ) }
				help={ sprintf(
					// translators: 1: number of characters typed. 2: the maximum number of allowed characters.
					__( '%1$d/%2$d characters', 'google-listings-and-ads' ),
					prompt.length,
					MAX_PROMPT_LENGTH
				) }
				value={ prompt }
				onChange={ setPrompt }
				disabled={ isGeneratingAssets }
				rows={ 4 }
				className={
					isOverLimit
						? 'gla-gen-ai-edit-image-modal__prompt--error'
						: undefined
				}
			/>
		</AppModal>
	);
}
