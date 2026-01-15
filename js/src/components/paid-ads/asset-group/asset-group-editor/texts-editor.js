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
import { useAppDispatch } from '~/data';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AppButton from '~/components/app-button';
import AppInputControl from '~/components/app-input-control';
import AssetItemActionButton, {
	ACTION_TYPES,
} from './asset-item-action-button';
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
 * Result returned by fillEmptyAssetSlotsWithUniqueValues.
 *
 * @typedef {Object} FillEmptyAssetSlotsResult
 * @property {string[]} assets Updated asset list.
 * @property {number} updatedCount Number of empty ("") slots that were filled.
 */

/**
 * Fill empty asset slots (represented by empty strings "") with unique
 * generated values.
 *
 * Existing non-empty values are preserved.
 * Empty slots that cannot be filled remain as "".
 *
 * @param {string[]} currentAssets Current asset values, where "" represents an empty slot.
 * @param {string[]} generatedAssets Newly generated candidate asset values.
 *
 * @return {FillEmptyAssetSlotsResult} Result containing updated assets and count of filled slots.
 */
export function fillEmptyAssetSlotsWithUniqueValues(
	currentAssets,
	generatedAssets
) {
	const existingAssetValues = new Set( currentAssets.filter( Boolean ) );

	let generatedIndex = 0;
	let updatedCount = 0;

	const assets = currentAssets.map( ( assetValue ) => {
		if ( assetValue !== '' ) {
			return assetValue;
		}

		while (
			generatedIndex < generatedAssets.length &&
			existingAssetValues.has( generatedAssets[ generatedIndex ] )
		) {
			generatedIndex++;
		}

		if ( generatedIndex < generatedAssets.length ) {
			const nextGeneratedValue = generatedAssets[ generatedIndex ];
			existingAssetValues.add( nextGeneratedValue );
			generatedIndex++;
			updatedCount++;
			return nextGeneratedValue;
		}

		return '';
	} );

	return { assets, updatedCount };
}

/**
 * Renders a list of text inputs for managing the single type of asset texts.
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
	const { fetchGenAITextAssets } = useAppDispatch();
	const [ texts, setTexts ] = useState( initialTexts );
	const [ isGeneratingAssets, setIsGeneratingAssets ] = useState( false );

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
		setIsGeneratingAssets( true );

		try {
			const response = await fetchGenAITextAssets( finalUrl, assetKey );
			const generatedTextAssets = response?.data?.[ assetKey ] ?? [];

			const { assets: updatedTexts, updatedCount } =
				fillEmptyAssetSlotsWithUniqueValues(
					texts,
					generatedTextAssets
				);

			if ( updatedCount > 0 ) {
				updateTexts( updatedTexts );
			} else {
				createNotice(
					'info',
					__(
						'No texts were generated. Please try again.',
						'google-listings-and-ads'
					)
				);
			}
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Something went wrong while generating texts. Please try again.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setIsGeneratingAssets( false );
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
							key={ index }
							className="gla-texts-editor__text-item"
						>
							<AppInputControl
								className="gla-texts-editor__text-input"
								value={ text }
								kindCharacterCount="google-ads"
								maxCharacterCount={ maxCharacterCount }
								placeholder={ placeholder }
								data-index={ index }
								onChange={ handleChange }
							/>
							<div className="gla-texts-editor__remove-text-button-anchor">
								{ index + 1 > minNumberOfTexts && (
									<AppButton
										className="gla-texts-editor__remove-text-button"
										aria-label={ __(
											'Remove text',
											'google-listings-and-ads'
										) }
										icon={ <GridiconCrossSmall /> }
										iconSize={ 20 }
										data-index={ index }
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
				hidden={
					minNumberOfTexts > 0 &&
					minNumberOfTexts === maxNumberOfTexts
				}
				disabled={
					maxNumberOfTexts > 0 && texts.length >= maxNumberOfTexts
				}
				text={ addButtonText }
				onClick={ handleAddClick }
			/>

			{ emptyFieldsCount > 0 && (
				<AssetItemActionButton
					action={ ACTION_TYPES.GENERATE }
					text={ generateButtonText }
					onClick={ handleGenerateClick }
					loading={ isGeneratingAssets }
				/>
			) }
		</div>
	);
}
