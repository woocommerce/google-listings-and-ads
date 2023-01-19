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
import AppButton from '.~/components/app-button';
import AppInputControl from '.~/components/app-input-control';
import AddAssetItemButton from './add-asset-item-button';
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
 * Renders a list of text inputs for managing the single type of asset texts.
 *
 * @param {Object} props React props.
 * @param {string[]} [props.initialTexts=[]] Initial texts.
 * @param {number} [props.minNumberOfTexts=0] Minimum number of texts.
 * @param {number} [props.maxNumberOfTexts=0] Maximum number of texts.
 * @param {number|number[]} props.maxCharacterCounts Maximum number of characters for each text. If the limits are the same, a single number can be used instead of an array.
 * @param {string} props.addButtonText Text for the button to add a new text input.
 * @param {string} [props.placeholder] Placeholder text.
 * @param {(texts: Array<string>) => void} [props.onChange] Callback function to be called when the texts are changed.
 */
export default function TextsEditor( {
	initialTexts = [],
	minNumberOfTexts = 0,
	maxNumberOfTexts = 0,
	maxCharacterCounts,
	addButtonText,
	placeholder,
	onChange = noop,
} ) {
	const updateTextsRef = useRef();
	const [ texts, setTexts ] = useState( () => {
		const nextTexts = normalizeNumberOfTexts(
			initialTexts,
			minNumberOfTexts,
			maxNumberOfTexts
		);
		if ( nextTexts.length !== initialTexts.length ) {
			onChange( nextTexts );
		}
		return nextTexts;
	} );

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

	const normalizedMaxCharacterCounts = [ maxCharacterCounts ].flat();

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
			<AddAssetItemButton
				aria-label={ __( 'Add text', 'google-listings-and-ads' ) }
				disabled={
					maxNumberOfTexts > 0 && texts.length >= maxNumberOfTexts
				}
				text={ addButtonText }
				onClick={ handleAddClick }
			/>
		</div>
	);
}
