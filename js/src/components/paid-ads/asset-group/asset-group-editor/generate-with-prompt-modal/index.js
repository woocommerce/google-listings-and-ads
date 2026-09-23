/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
// eslint-disable-next-line import/named, @woocommerce/dependency-group -- ProgressBar exists in @wordpress/components build output but isn't exported from its type entry point.
import { TextareaControl, Notice, ProgressBar } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GEN_AI_ASSET_TYPES } from '~/constants';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import ProgressGraphics from '~/images/pmax-assets-improvements/gen-ai-progress.svg';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import './index.scss';

const MAX_PROMPT_LENGTH = 1500;

/**
 * Modal that generates a net-new image from a free-text prompt in freeform mode. The generated
 * image lands in the section's AI-generated images grid, where the user picks which images to add.
 *
 * Owns its own {@link useCreateGenAIAssets} instance so Cancel aborts this request only, without
 * touching the grid or the initial generation flow.
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

	const canGenerate =
		prompt.trim().length > 0 && prompt.length <= MAX_PROMPT_LENGTH;

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
								isPrimary
							>
								{ __( 'Generate', 'google-listings-and-ads' ) }
							</AppButton>,
					  ]
			}
		>
			{ isGeneratingAssets ? (
				<div className="gla-generate-with-prompt-modal__progress">
					<img
						src={ ProgressGraphics }
						alt=""
						width={ 212 }
						height={ 212 }
					/>
					<h2 className="gla-generate-with-prompt-modal__progress-title">
						{ __( 'Generating assets', 'google-listings-and-ads' ) }
					</h2>
					<ProgressBar className="gla-generate-with-prompt-modal__progress-bar" />
				</div>
			) : (
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
						value={ prompt }
						onChange={ ( value ) =>
							setPrompt( value.slice( 0, MAX_PROMPT_LENGTH ) )
						}
						rows={ 4 }
						__nextHasNoMarginBottom
						hideLabelFromVision
					/>

					<span className="gla-generate-with-prompt-modal__character-count">
						{ sprintf(
							// translators: %1$d: current character count, %2$d: maximum allowed characters.
							__(
								'%1$d/%2$d characters',
								'google-listings-and-ads'
							),
							prompt.length,
							MAX_PROMPT_LENGTH
						) }
					</span>
				</>
			) }
		</AppModal>
	);
}
