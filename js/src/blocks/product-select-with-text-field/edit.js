/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import {
	__experimentalUseProductEntityProp as useProductEntityProp,
	__experimentalTextControl as TextControl,
} from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { Label } from '../components';

const FALLBACK_VALUE = '';

export default function Edit( { attributes, context } ) {
	const { options, customInputValue } = attributes;

	const blockProps = useWooBlockProps( attributes );
	const [ value, setValue ] = useProductEntityProp( attributes.property, {
		postType: context.postType,
		fallbackValue: FALLBACK_VALUE,
	} );

	// Refer to the "Derived value for initialization" in README for why this `useState`
	// can initialize directly.
	const [ optionValue, setOptionValue ] = useState( () => {
		// Empty string is a valid option value.
		const initValue = value ?? FALLBACK_VALUE;
		const selectedOption = options.find(
			( option ) => option.value === initValue
		);

		return selectedOption?.value ?? customInputValue;
	} );

	const isSelectedCustomInput = optionValue === customInputValue;

	const [ text, setText ] = useState( isSelectedCustomInput ? value : '' );

	const handleSelectionChange = ( nextOptionValue ) => {
		setOptionValue( nextOptionValue );

		if ( nextOptionValue === customInputValue ) {
			setValue( text );
		} else {
			setValue( nextOptionValue );
		}
	};

	const handleTextChange = ( nextText ) => {
		setText( nextText );
		setValue( nextText );
	};

	return (
		<div { ...blockProps }>
			<SelectControl
				label={
					<Label
						label={ attributes.label }
						tooltip={ attributes.tooltip }
					/>
				}
				options={ options }
				value={ optionValue }
				onChange={ handleSelectionChange }
			/>
			{ isSelectedCustomInput && (
				<TextControl
					type="text"
					value={ text }
					onChange={ handleTextChange }
				/>
			) }
		</div>
	);
}
