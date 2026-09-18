/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	TextareaControl,
	Notice,
	__experimentalText as Text,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GEN_AI_ASSET_TYPES } from '~/constants';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';

const MAX_PROMPT_LENGTH = 1500;

/**
 * Modal that generates a net-new image from a free-text prompt in freeform mode and appends it
 * to a marketing-image section's grid.
 *
 * Owns its own {@link useCreateGenAIAssets} instance so Cancel aborts this request only, without
 * touching the grid or the initial generation flow.
 *
 * @param {Object} props React props.
 * @param {string} props.finalUrl The campaign's final URL the assets are keyed by.
 * @param {string} props.assetKey The asset type / aspect ratio, e.g. 'marketing_image'.
 * @param {(urls: string[]) => void} props.onAddImages Called with the generated image URLs to append to the section.
 * @param {Function} props.onRequestClose Called to close the modal.
 */
export default function GenerateWithPromptModal( {
	finalUrl,
	assetKey,
	onAddImages,
	onRequestClose,
} ) {
	const { generateAssets, isGeneratingAssets, abortGenerateAssets } =
		useCreateGenAIAssets();
	const [ prompt, setPrompt ] = useState( '' );
	const [ hasError, setHasError ] = useState( false );

	const isTooLong = prompt.length > MAX_PROMPT_LENGTH;
	const canGenerate = prompt.trim().length > 0 && ! isTooLong;

	const handleCancel = () => {
		abortGenerateAssets();
		onRequestClose();
	};

	const handleGenerate = async () => {
		setHasError( false );

		const result = await generateAssets( finalUrl, [
			{ type: GEN_AI_ASSET_TYPES.MEDIA, assetKey, prompt },
		] );

		const generatedUrls =
			result?.[ GEN_AI_ASSET_TYPES.MEDIA ]?.[ assetKey ] ?? [];

		if ( generatedUrls.length === 0 ) {
			setHasError( true );
			return;
		}

		onAddImages( generatedUrls );
		onRequestClose();
	};

	return (
		<AppModal
			className="gla-generate-with-prompt-modal"
			title={ __( 'Generate a new image', 'google-listings-and-ads' ) }
			onRequestClose={ handleCancel }
			buttons={ [
				<AppButton key="cancel" isTertiary onClick={ handleCancel }>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="generate"
					isPrimary
					disabled={ ! canGenerate }
					loading={ isGeneratingAssets }
					onClick={ handleGenerate }
				>
					{ __( 'Generate', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			<p>
				{ __(
					"Describe the direction and style you'd like to generate.",
					'google-listings-and-ads'
				) }
			</p>

			{ hasError && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Something went wrong while generating the image. Please try again.',
						'google-listings-and-ads'
					) }
				</Notice>
			) }

			<TextareaControl
				__nextHasNoMarginBottom
				label={ __(
					'Image generation prompt',
					'google-listings-and-ads'
				) }
				hideLabelFromVision
				placeholder={ __(
					'Example: Generate an image of light grey canvas sneakers with soft studio lighting on an off-white background. Photorealistic, centred composition, matte texture.',
					'google-listings-and-ads'
				) }
				value={ prompt }
				onChange={ setPrompt }
				rows={ 4 }
			/>

			<Text variant="muted" size="12">
				{ sprintf(
					// translators: %1$d: current character count, %2$d: maximum allowed characters.
					__( '%1$d/%2$d characters', 'google-listings-and-ads' ),
					prompt.length,
					MAX_PROMPT_LENGTH
				) }
			</Text>
		</AppModal>
	);
}
