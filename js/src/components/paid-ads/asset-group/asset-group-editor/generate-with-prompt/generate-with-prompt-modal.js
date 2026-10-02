/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import GenAIProgress from '~/components/paid-ads/gen-ai-progress';
import GenAIPromptControl from '../gen-ai-prompt-control';
import './generate-with-prompt-modal.scss';

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
 * @property {number} prompt_length The number of characters in the prompt when the modal was dismissed.
 */

/**
 * Triggered when the "Generate" button in the "Generate with prompt" modal is clicked.
 *
 * @event gla_gen_ai_generate_with_prompt_modal_generate_button_click
 * @property {string} asset_key The asset key the image is generated for.
 * @property {number} prompt_length The number of characters in the submitted prompt.
 */

/**
 * Triggered when a generation request from the "Generate with prompt" modal returns at least one image.
 *
 * @event gla_gen_ai_generate_with_prompt_modal_generation_completed
 * @property {string} asset_key The asset key the image is generated for.
 * @property {number} prompt_length The number of characters in the submitted prompt.
 * @property {number} num_generated_images The number of images generated.
 */

/**
 * Triggered when a generation request from the "Generate with prompt" modal returns no image.
 *
 * @event gla_gen_ai_generate_with_prompt_modal_generation_failed
 * @property {string} asset_key The asset key the image is generated for.
 * @property {number} prompt_length The number of characters in the submitted prompt.
 * @property {string} reason `error` when the request failed with an error notice, `empty` when it returned no image, `unexpected` when an unexpected error occurred.
 */

/**
 * Modal to generate a new image from a text prompt.
 *
 * @fires gla_gen_ai_generate_with_prompt_modal_shown with `{ asset_key }` when the modal is shown.
 * @fires gla_gen_ai_generate_with_prompt_modal_close with `{ asset_key, prompt_length }` when the modal is dismissed.
 * @fires gla_gen_ai_generate_with_prompt_modal_generate_button_click with `{ asset_key, prompt_length }` when the "Generate" button is clicked.
 * @fires gla_gen_ai_generate_with_prompt_modal_generation_completed with `{ asset_key, prompt_length, num_generated_images }` when a generation request returns images.
 * @fires gla_gen_ai_generate_with_prompt_modal_generation_failed with `{ asset_key, prompt_length, reason }` when a generation request returns no image.
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
	const isCancelledRef = useRef( false );

	useEffect( () => {
		recordGlaEvent( 'gla_gen_ai_generate_with_prompt_modal_shown', {
			asset_key: assetKey,
		} );
	}, [ assetKey ] );

	const canGenerate = prompt.trim().length > 0;
	const eventProps = {
		asset_key: assetKey,
		prompt_length: prompt.length,
	};

	const recordGenerationFailed = ( reason ) => {
		recordGlaEvent(
			'gla_gen_ai_generate_with_prompt_modal_generation_failed',
			{ ...eventProps, reason }
		);
	};

	const handleCancel = () => {
		isCancelledRef.current = true;
		abortGenerateAssets();
		recordGlaEvent(
			'gla_gen_ai_generate_with_prompt_modal_close',
			eventProps
		);
		onRequestClose();
	};

	const handleGenerate = async () => {
		setHasError( false );

		const result = await generateAssets( finalUrl, [
			{ type: GEN_AI_ASSET_TYPES.MEDIA, assetKey, prompt },
		] );

		if ( isCancelledRef.current ) {
			return;
		}

		// The hook already showed an "unexpected error" notice.
		if ( ! result ) {
			recordGenerationFailed( 'unexpected' );
			return;
		}

		// The hook already showed an error notice for the failed media request.
		if ( result.erroredTypes.includes( GEN_AI_ASSET_TYPES.MEDIA ) ) {
			recordGenerationFailed( 'error' );
			return;
		}

		const generatedUrls =
			result[ GEN_AI_ASSET_TYPES.MEDIA ]?.[ assetKey ] ?? [];

		// No notice covers a request that produced no image, so show the inline error.
		if ( ! generatedUrls.length ) {
			recordGenerationFailed( 'empty' );
			setHasError( true );
			return;
		}

		recordGlaEvent(
			'gla_gen_ai_generate_with_prompt_modal_generation_completed',
			{
				...eventProps,
				num_generated_images: generatedUrls.length,
			}
		);
		onRequestClose();
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
								eventProps={ eventProps }
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

					<GenAIPromptControl
						label={ __(
							'Image generation prompt',
							'google-listings-and-ads'
						) }
						placeholder={ __(
							'Example: Generate an image of light grey canvas sneakers with soft studio lighting on an off-white background. Photorealistic, centred composition, matte texture.',
							'google-listings-and-ads'
						) }
						value={ prompt }
						onChange={ setPrompt }
					/>
				</>
			) }
		</AppModal>
	);
}
