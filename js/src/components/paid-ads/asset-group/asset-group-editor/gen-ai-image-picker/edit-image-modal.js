/**
 * External dependencies
 */
import { Flex, FlexItem, Notice } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';
import { useAppDispatch } from '~/data';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import GenAIProgress from '~/components/paid-ads/gen-ai-progress';
import GenAIPromptControl from '../gen-ai-prompt-control';
import './edit-image-modal.scss';

/**
 * Triggered when the "Edit image" modal is shown.
 *
 * @event gla_gen_ai_edit_image_modal_shown
 * @property {string} asset_key The asset key the edited image belongs to.
 */

/**
 * Triggered when the "Edit image" modal is dismissed.
 *
 * @event gla_gen_ai_edit_image_modal_close
 * @property {string} asset_key The asset key the edited image belongs to.
 * @property {number} prompt_length The number of characters in the trimmed prompt when the modal was dismissed.
 */

/**
 * Triggered when the "Generate" button in the "Edit image" modal is clicked.
 *
 * @event gla_gen_ai_edit_image_modal_generate_button_click
 * @property {string} asset_key The asset key the edited image belongs to.
 * @property {number} prompt_length The number of characters in the submitted, trimmed prompt.
 */

/**
 * Triggered when a generation request from the "Edit image" modal returns the edited image.
 *
 * @event gla_gen_ai_edit_image_modal_generation_completed
 * @property {string} asset_key The asset key the edited image belongs to.
 * @property {number} prompt_length The number of characters in the submitted, trimmed prompt.
 */

/**
 * Triggered when a generation request from the "Edit image" modal returns no image.
 *
 * @event gla_gen_ai_edit_image_modal_generation_failed
 * @property {string} asset_key The asset key the edited image belongs to.
 * @property {number} prompt_length The number of characters in the submitted, trimmed prompt.
 * @property {string} reason `error` when the request failed with an error notice, `empty` when it returned no image, `unexpected` when an unexpected error occurred.
 */

/**
 * Modal for editing a single GenAI-generated image via a prompt.
 * Regenerates the image in recontext mode, preserving the source image's aspect ratio,
 * and replaces it in place on success.
 *
 * @fires gla_gen_ai_edit_image_modal_shown with `{ asset_key }` when the modal is shown.
 * @fires gla_gen_ai_edit_image_modal_close with `{ asset_key, prompt_length }` when the modal is dismissed.
 * @fires gla_gen_ai_edit_image_modal_generate_button_click with `{ asset_key, prompt_length }` when the "Generate" button is clicked.
 * @fires gla_gen_ai_edit_image_modal_generation_completed with `{ asset_key, prompt_length }` when a generation request returns the edited image.
 * @fires gla_gen_ai_edit_image_modal_generation_failed with `{ asset_key, prompt_length, reason }` when a generation request returns no image.
 *
 * @param {Object} props React props.
 * @param {string} props.finalUrl The final URL the source image was generated for.
 * @param {string} props.assetKey The asset key (e.g. `marketing_image`) the source image belongs to.
 * @param {string} props.sourceImageUrl The `temporary_image_url` of the image being edited.
 * @param {string} props.displayImageUrl The (possibly proxied) URL to render for the source thumbnail.
 * @param {Function} props.onReplaceImage Callback invoked with `(sourceImageUrl, newImageUrl)` when the image has been replaced in place.
 * @param {Function} props.onRequestClose Callback invoked when the modal should close.
 */
export default function EditImageModal( {
	finalUrl,
	assetKey,
	sourceImageUrl,
	displayImageUrl,
	onReplaceImage,
	onRequestClose,
} ) {
	const [ prompt, setPrompt ] = useState( '' );
	const [ hasError, setHasError ] = useState( false );
	const isCancelledRef = useRef( false );
	const { generateAssets, isGeneratingAssets, abortGenerateAssets } =
		useCreateGenAIAssets();
	const { replaceGenAIMediaAsset } = useAppDispatch();

	const trimmedPrompt = prompt.trim();
	const eventProps = {
		asset_key: assetKey,
		prompt_length: trimmedPrompt.length,
	};

	useEffect( () => {
		recordGlaEvent( 'gla_gen_ai_edit_image_modal_shown', {
			asset_key: assetKey,
		} );
	}, [ assetKey ] );

	const recordGenerationFailed = ( reason ) => {
		recordGlaEvent( 'gla_gen_ai_edit_image_modal_generation_failed', {
			...eventProps,
			reason,
		} );
	};

	const handleCancel = () => {
		isCancelledRef.current = true;
		abortGenerateAssets();
		recordGlaEvent( 'gla_gen_ai_edit_image_modal_close', eventProps );
		onRequestClose();
	};

	const handleGenerate = async () => {
		setHasError( false );

		const result = await generateAssets( finalUrl, [
			{
				type: GEN_AI_ASSET_TYPES.MEDIA,
				assetKey,
				prompt: trimmedPrompt,
				sourceImageUrl,
			},
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

		const [ newImageUrl ] =
			result[ GEN_AI_ASSET_TYPES.MEDIA ]?.[ assetKey ] ?? [];

		// No notice covers a request that produced no image, so show the inline error.
		if ( ! newImageUrl ) {
			recordGenerationFailed( 'empty' );
			setHasError( true );
			return;
		}

		recordGlaEvent(
			'gla_gen_ai_edit_image_modal_generation_completed',
			eventProps
		);

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
			size="large"
			onRequestClose={ handleCancel }
			shouldCloseOnClickOutside={ ! isGeneratingAssets }
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
								disabled={ ! trimmedPrompt }
								onClick={ handleGenerate }
								eventName="gla_gen_ai_edit_image_modal_generate_button_click"
								eventProps={ eventProps }
								isPrimary
							>
								{ __( 'Generate', 'google-listings-and-ads' ) }
							</AppButton>,
					  ]
			}
		>
			{ isGeneratingAssets ? (
				<GenAIProgress
					title={ __(
						'Generating asset',
						'google-listings-and-ads'
					) }
				/>
			) : (
				<>
					<p className="gla-gen-ai-edit-image-modal__description">
						{ __(
							'Describe your edits. You can change the background, environment, or angle, not the product itself.',
							'google-listings-and-ads'
						) }
					</p>

					{ hasError && (
						<Notice status="error" isDismissible={ false }>
							{ __(
								'Something went wrong while editing the image. Please try again.',
								'google-listings-and-ads'
							) }
						</Notice>
					) }

					<Flex
						className="gla-gen-ai-edit-image-modal__row"
						align="stretch"
						direction={ [ 'column', 'row' ] }
						gap={ 6 }
					>
						<FlexItem>
							<img
								className="gla-gen-ai-edit-image-modal__thumbnail"
								src={ displayImageUrl }
								height="280"
								width="280"
								alt=""
							/>
						</FlexItem>

						<FlexItem isBlock>
							<GenAIPromptControl
								value={ prompt }
								onChange={ setPrompt }
								placeholder={ __(
									'Example: Make the background blue',
									'google-listings-and-ads'
								) }
								rows={ 12 }
							/>
						</FlexItem>
					</Flex>
				</>
			) }
		</AppModal>
	);
}
