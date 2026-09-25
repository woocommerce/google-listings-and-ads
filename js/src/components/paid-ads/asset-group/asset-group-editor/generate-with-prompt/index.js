/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AssetItemActionButton, {
	ACTION_TYPES,
} from '../asset-item-action-button';
import GenerateWithPromptModal from './generate-with-prompt-modal';

/**
 * Triggered when the "Generate with prompt" button of an image section is clicked.
 *
 * @event gla_gen_ai_generate_with_prompt_click
 * @property {string} asset_key The asset key of the image section.
 */

/**
 * Renders the "Generate with prompt" button and the modal it opens.
 *
 * @fires gla_gen_ai_generate_with_prompt_click with `{ asset_key }` when the "Generate with prompt" button is clicked.
 *
 * @param {Object} props React props.
 * @param {string} props.finalUrl The campaign's final URL the assets are keyed by.
 * @param {string} props.assetKey The asset type / aspect ratio, e.g. 'marketing_image'.
 * @param {string} props.buttonLabel The text for the "Generate with prompt" button.
 * @param {string} [props.buttonAriaLabel] The accessible label for the "Generate with prompt" button.
 */
export default function GenerateWithPrompt( {
	finalUrl,
	assetKey,
	buttonLabel,
	buttonAriaLabel,
} ) {
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const openModal = () => {
		setIsModalOpen( true );
	};

	const closeModal = () => {
		setIsModalOpen( false );
	};

	return (
		<>
			<AssetItemActionButton
				action={ ACTION_TYPES.GENERATE }
				text={ buttonLabel }
				aria-label={ buttonAriaLabel }
				onClick={ openModal }
				eventName="gla_gen_ai_generate_with_prompt_click"
				eventProps={ { asset_key: assetKey } }
			/>

			{ isModalOpen && (
				<GenerateWithPromptModal
					finalUrl={ finalUrl }
					assetKey={ assetKey }
					onRequestClose={ closeModal }
				/>
			) }
		</>
	);
}
