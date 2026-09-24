/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { TextareaControl, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import GenAIProgress from '~/components/paid-ads/gen-ai-progress';
import './index.scss';

const MAX_PROMPT_LENGTH = 1500;

/**
 * Triggered when the "Generate with prompt" modal is shown.
 *
 * @event gla_gen_ai_generate_with_prompt_modal_shown
 * @property {string} asset_key The asset key the image is generated for.
 */

/**
 * Triggered when the "Generate with prompt" modal is dismissed.
 *
 * @event gla_gen_ai_generate_with_prompt_modal_close
 * @property {string} asset_key The asset key the image is generated for.
 */

/**
 * Triggered when the "Generate" button in the "Generate with prompt" modal is clicked.
 *
 * @event gla_gen_ai_generate_with_prompt_modal_generate_button_click
 * @property {string} asset_key The asset key the image is generated for.
 */

/**
 * Triggered when a generation request from the "Generate with prompt" modal completes.
 *
 * @event gla_gen_ai_generate_with_prompt_modal_generation_completed
 * @property {string} asset_key The asset key the image is generated for.
 * @property {number} num_generated_images The number of images generated.
 */

/**
 * Modal to generate a new image from a text prompt.
 *
 * @fires gla_gen_ai_generate_with_prompt_modal_shown with `{ asset_key }` when the modal is shown.
 * @fires gla_gen_ai_generate_with_prompt_modal_close with `{ asset_key }` when the modal is dismissed.
 * @fires gla_gen_ai_generate_with_prompt_modal_generate_button_click with `{ asset_key }` when the "Generate" button is clicked.
 * @fires gla_gen_ai_generate_with_prompt_modal_generation_completed with `{ asset_key, num_generated_images }` when a generation request completes.
 *
 * @param {Object} props React props.
 * @param {string} props.finalUrl The campaign's final URL the assets are keyed by.
 * @param {string} props.assetKey The asset type / aspect ratio, e.g. 'marketing_image'.
 * @param {Function} props.onRequestClose Called to close the modal.
 */
export default function GenerateWithPromptModal( {
	finalUrl,
	assetKey,
	onRequestClose,
} ) {
	const { generateAssets, isGeneratingAssets, abortGenerateAssets } =
		useCreateGenAIAssets();
	const [ prompt, setPrompt ] = useState( '' );
	const [ hasError, setHasError ] = useState( false );

	useEffect( () => {
		recordGlaEvent( 'gla_gen_ai_generate_with_prompt_modal_shown', {
			asset_key: assetKey,
		} );
	}, [ assetKey ] );

	const canGenerate = prompt.trim().length > 0;

	const handleCancel = () => {
		abortGenerateAssets();
		recordGlaEvent( 'gla_gen_ai_generate_with_prompt_modal_close', {
			asset_key: assetKey,
		} );
		onRequestClose();
	};

	const handlePromptChange = ( value ) => {
		setPrompt( value.slice( 0, MAX_PROMPT_LENGTH ) );
	};

	const handleGenerate = async () => {
		setHasError( false );

		const result = await generateAssets( finalUrl, [
			{ type: GEN_AI_ASSET_TYPES.MEDIA, assetKey, prompt },
		] );

		// Aborted, or an unexpected error the hook already reported with a notice.
		if ( ! result ) {
			return;
		}

		const generatedUrls =
			result[ GEN_AI_ASSET_TYPES.MEDIA ]?.[ assetKey ] ?? [];

		recordGlaEvent(
			'gla_gen_ai_generate_with_prompt_modal_generation_completed',
			{
				asset_key: assetKey,
				num_generated_images: generatedUrls.length,
			}
		);

		if ( generatedUrls.length > 0 ) {
			onRequestClose();
			return;
		}

		// The hook shows a notice for failed requests. The inline error covers requests that
		// produced no image without one.
		setHasError(
			! result.erroredTypes.includes( GEN_AI_ASSET_TYPES.MEDIA )
		);
	};

	return (
		<AppModal
			className="gla-generate-with-prompt-modal"
			title={ __( 'Generate a new image', 'google-listings-and-ads' ) }
			onRequestClose={ handleCancel }
			buttons={
				isGeneratingAssets
					? []
					: [
							<AppButton
								key="cancel"
								onClick={ handleCancel }
								isTertiary
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="generate"
								disabled={ ! canGenerate }
								onClick={ handleGenerate }
								eventName="gla_gen_ai_generate_with_prompt_modal_generate_button_click"
								eventProps={ { asset_key: assetKey } }
								isPrimary
							>
								{ __( 'Generate', 'google-listings-and-ads' ) }
							</AppButton>,
					  ]
			}
		>
			{ isGeneratingAssets && (
				<GenAIProgress
					title={ __(
						'Generating asset',
						'google-listings-and-ads'
					) }
				/>
			) }

			{ ! isGeneratingAssets && (
				<>
					<p className="gla-generate-with-prompt-modal__description">
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
						label={ __(
							'Image generation prompt',
							'google-listings-and-ads'
						) }
						placeholder={ __(
							'Example: Generate an image of light grey canvas sneakers with soft studio lighting on an off-white background. Photorealistic, centred composition, matte texture.',
							'google-listings-and-ads'
						) }
						help={ sprintf(
							// translators: %1$d: current character count, %2$d: maximum allowed characters.
							__(
								'%1$d/%2$d characters',
								'google-listings-and-ads'
							),
							prompt.length,
							MAX_PROMPT_LENGTH
						) }
						value={ prompt }
						onChange={ handlePromptChange }
						rows={ 4 }
						__nextHasNoMarginBottom
						hideLabelFromVision
					/>
				</>
			) }
		</AppModal>
	);
}
