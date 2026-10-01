/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';
import { useState } from '@wordpress/element';
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
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import GenAIProgress from '~/components/paid-ads/gen-ai-progress';
import GenAIPromptControl from '~/components/paid-ads/asset-group/asset-group-editor/gen-ai-prompt-control';
import './index.scss';

/**
 * Triggered when the "Generate" button in the "Edit image" modal is clicked.
 *
 * @event gla_gen_ai_edit_image_modal_generate_button_click
 * @property {string} asset_key The asset key the image belongs to.
 */

/**
 * Triggered when a generation request from the "Edit image" modal completes.
 *
 * @event gla_gen_ai_edit_image_modal_generation_completed
 * @property {string} asset_key The asset key the image belongs to.
 * @property {boolean} is_successful Whether an edited image was returned and replaced the source image.
 */

/**
 * Modal for editing a single GenAI-generated image via a prompt.
 * Regenerates the image in recontext mode, preserving the source image's aspect ratio,
 * and replaces it in place on success.
 *
 * @fires gla_gen_ai_edit_image_modal_generate_button_click with `{ asset_key }` when the "Generate" button is clicked.
 * @fires gla_gen_ai_edit_image_modal_generation_completed with `{ asset_key, is_successful }` when a generation request completes.
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
	const { generateAssets, isGeneratingAssets, abortGenerateAssets } =
		useCreateGenAIAssets();
	const { replaceGenAIMediaAsset } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const trimmedPrompt = prompt.trim();

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

		if ( ! result ) {
			return;
		}

		const [ newImageUrl ] =
			result[ GEN_AI_ASSET_TYPES.MEDIA ]?.[ assetKey ] ?? [];

		recordGlaEvent( 'gla_gen_ai_edit_image_modal_generation_completed', {
			asset_key: assetKey,
			is_successful: Boolean( newImageUrl ),
		} );

		// The hook already showed a notice for a failed request.
		if ( result.erroredTypes?.includes( GEN_AI_ASSET_TYPES.MEDIA ) ) {
			return;
		}

		if ( ! newImageUrl ) {
			createNotice(
				'error',
				__(
					'Something went wrong while editing the image. Please try again.',
					'google-listings-and-ads'
				)
			);
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
								isSecondary
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="generate"
								disabled={ ! trimmedPrompt }
								onClick={ handleGenerate }
								eventName="gla_gen_ai_edit_image_modal_generate_button_click"
								eventProps={ { asset_key: assetKey } }
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
