/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import GridiconCrossSmall from 'gridicons/dist/cross-small';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AppInputControl from '.~/components/app-input-control';
import AddAssetItemButton from './add-asset-item-button';
import './texts-editor.scss';

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
 * @param {(texts: Array<string>) => void} props.onChange Callback function to be called when the texts are changed.
 */
export default function TextsEditor( {
	initialTexts = [],
	minNumberOfTexts = 0,
	maxNumberOfTexts = 0,
	maxCharacterCounts,
	addButtonText,
	placeholder,
	onChange,
} ) {
	const [ texts, setTexts ] = useState( () => {
		const shortage = minNumberOfTexts - initialTexts.length;
		if ( shortage <= 0 ) {
			return initialTexts;
		}

		const supplement = Array.from( { length: shortage }, () => '' );
		return initialTexts.concat( supplement );
	} );

	const updateTexts = ( nextTexts ) => {
		setTexts( nextTexts );
		onChange( nextTexts );
	};

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
