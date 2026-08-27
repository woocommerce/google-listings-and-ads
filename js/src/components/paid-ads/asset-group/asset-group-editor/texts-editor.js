/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState, useEffect, useRef } from '@wordpress/element';
import GridiconCrossSmall from 'gridicons/dist/cross-small';

/**
 * Internal dependencies
 */
import { GEN_AI_ASSET_TYPES } from '~/constants';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGenAITextAssets from '~/hooks/useGenAITextAssets';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import AppButton from '~/components/app-button';
import AppInputControl from '~/components/app-input-control';
import AssetItemActionButton, {
	ACTION_TYPES,
} from './asset-item-action-button';
import AIIcon from '~/images/ai-icon.svg?inline';
import fillEmptyAssetSlots from './utils/fill-empty-asset-slots';
import './texts-editor.scss';

function normalizeNumberOfTexts( texts, minNumberOfTexts, maxNumberOfTexts ) {
	const shortage = Math.max( minNumberOfTexts - texts.length, 0 );
	const supplement = Array.from( { length: shortage }, () => '' );
	const sliceArgs = [ 0 ];
	if ( maxNumberOfTexts > 0 ) {
		sliceArgs.push( maxNumberOfTexts );
	}
	return texts.concat( supplement ).slice( ...sliceArgs );
}

/**
 * Triggered when the generate texts button is clicked in the TextsEditor component. Event properties include finalUrl and assetKey.
 *
 * @event gla_texts_editor_generate_button_click
 * @property {string} finalUrl The final URL for the ad.
 * @property {string} assetKey The key of the text asset.
 */

/**
 * Renders a list of text inputs for managing the single type of asset texts.
 *
 * @fires gla_texts_editor_generate_button_click when the generate texts button is clicked in the TextsEditor component.
 *
 * @param {Object} props React props.
 * @param {string} props.assetKey Key of the text asset.
 * @param {string} props.finalUrl The final URL for the ad.
 * @param {string[]} [props.initialTexts=[]] Initial texts.
 * @param {number} [props.minNumberOfTexts=0] Minimum number of texts.
 * @param {number} [props.maxNumberOfTexts=0] Maximum number of texts.
 * @param {number|number[]} props.maxCharacterCounts Maximum number of characters for each text. If the limits are the same, a single number can be used instead of an array.
 * @param {string} props.addButtonText Text for the button to add a new text input.
 * @param {string} [props.generateButtonPluralText] Text for the button to generate texts using AI.
 * @param {string} [props.generateButtonSingularText] Text for the button to generate a single text using AI.
 * @param {string} [props.placeholder] Placeholder text.
 * @param {JSX.Element} [props.children] Content to be rendered above the add button.
 * @param {(texts: Array<string>) => void} [props.onChange] Callback function to be called when the texts are changed.
 */
export default function TextsEditor( {
	assetKey,
	finalUrl,
	initialTexts = [],
	minNumberOfTexts = 0,
	maxNumberOfTexts = 0,
	maxCharacterCounts,
	addButtonText,
	generateButtonPluralText,
	generateButtonSingularText,
	placeholder,
	children,
	onChange = noop,
} ) {
	const updateTextsRef = useRef();
	const { createNotice } = useDispatchCoreNotices();
	const { generateAssets, isGeneratingAssets } = useCreateGenAIAssets();
	const [ texts, setTexts ] = useState( initialTexts );
	const { assets: genAITextAssets } = useGenAITextAssets(
		finalUrl,
		assetKey
	);

	const updateTexts = ( nextTexts ) => {
		setTexts( nextTexts );
		onChange( nextTexts );
	};
	updateTextsRef.current = updateTexts;

	useEffect( () => {
		if (
			( maxNumberOfTexts > 0 && texts.length > maxNumberOfTexts ) ||
			( minNumberOfTexts > 0 && texts.length < minNumberOfTexts )
		) {
			updateTextsRef.current(
				normalizeNumberOfTexts(
					texts,
					minNumberOfTexts,
					maxNumberOfTexts
				)
			);
		}
	}, [ texts, maxNumberOfTexts, minNumberOfTexts ] );

	const handleChange = ( text, { event } ) => {
		const { index } = event.target.dataset;
		const nextTexts = [ ...texts ];
		nextTexts[ index ] = text.trim();
		updateTexts( nextTexts );
	};

	const handleRemoveClick = ( event ) => {
		const { index } = event.currentTarget.dataset;
		const nextTexts = [ ...texts ];
		nextTexts.splice( index, 1 );
		updateTexts( nextTexts );
	};

	const handleAddClick = () => {
		updateTexts( texts.concat( '' ) );
	};

	const handleGenerateClick = async () => {
		try {
			const generatedAssets = await generateAssets( finalUrl, [
				{
					type: GEN_AI_ASSET_TYPES.TEXT,
					assetKey,
				},
			] );

			const generatedTextAssets =
				generatedAssets?.[ GEN_AI_ASSET_TYPES.TEXT ]?.[ assetKey ] ??
				[];

			const { assets: updatedTexts, updatedCount } = fillEmptyAssetSlots(
				texts,
				generatedTextAssets
			);

			if ( updatedCount > 0 ) {
				updateTexts( updatedTexts );
			} else if (
				! generatedAssets?.erroredTypes?.includes(
					GEN_AI_ASSET_TYPES.TEXT
				)
			) {
				// Skip this generic notice when the request itself failed —
				// generateAssets() already showed a more specific error notice.
				createNotice(
					'info',
					__(
						'No texts were generated. Please try again.',
						'google-listings-and-ads'
					)
				);
			}
		} catch ( error ) {
			// generateAssets() catches and notices API-level failures itself, so
			// this only guards against unexpected runtime errors (e.g. a bug in
			// fillEmptyAssetSlots) — not a request that actually failed.
			createNotice(
				'error',
				__(
					'Something went wrong while generating texts. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const normalizedMaxCharacterCounts = [ maxCharacterCounts ].flat();
	const emptyFieldsCount = texts.filter( ( value ) => value === '' ).length;
	let generateButtonText;

	if ( emptyFieldsCount === 1 && generateButtonSingularText ) {
		generateButtonText = generateButtonSingularText;
	} else if ( emptyFieldsCount > 1 && generateButtonPluralText ) {
		generateButtonText = generateButtonPluralText;
	}

	return (
		<div className="gla-texts-editor">
			<div className="gla-texts-editor__text-list">
				{ texts.map( ( text, index ) => {
					const maxCharacterCount =
						normalizedMaxCharacterCounts[ index ] ??
						normalizedMaxCharacterCounts[ 0 ];

					return (
						<div
							className="gla-texts-editor__text-item"
							key={ index }
						>
							<AppInputControl
								className="gla-texts-editor__text-input"
								data-index={ index }
								kindCharacterCount="google-ads"
								maxCharacterCount={ maxCharacterCount }
								onChange={ handleChange }
								placeholder={ placeholder }
								suffix={
									genAITextAssets?.includes( text ) && (
										<AIIcon
											className="gla-texts-editor__ai-icon"
											height={ 24 }
											width={ 24 }
										/>
									)
								}
								value={ text }
							/>
							<div className="gla-texts-editor__remove-text-button-anchor">
								{ index + 1 > minNumberOfTexts && (
									<AppButton
										aria-label={ __(
											'Remove text',
											'google-listings-and-ads'
										) }
										className="gla-texts-editor__remove-text-button"
										data-index={ index }
										icon={ <GridiconCrossSmall /> }
										iconSize={ 20 }
										onClick={ handleRemoveClick }
									/>
								) }
							</div>
						</div>
					);
				} ) }
			</div>
			{ children }
			<AssetItemActionButton
				aria-label={ __( 'Add text', 'google-listings-and-ads' ) }
				disabled={
					maxNumberOfTexts > 0 && texts.length >= maxNumberOfTexts
				}
				hidden={
					minNumberOfTexts > 0 &&
					minNumberOfTexts === maxNumberOfTexts
				}
				onClick={ handleAddClick }
				text={ addButtonText }
			/>

			{ emptyFieldsCount > 0 && generateButtonText && (
				<AssetItemActionButton
					action={ ACTION_TYPES.GENERATE }
					eventName="gla_texts_editor_generate_button_click"
					eventProps={ {
						finalUrl,
						assetKey,
					} }
					loading={ isGeneratingAssets }
					onClick={ handleGenerateClick }
					text={ generateButtonText }
				/>
			) }
		</div>
	);
}
