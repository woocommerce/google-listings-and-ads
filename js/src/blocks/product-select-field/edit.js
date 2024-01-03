/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { SelectControl } from '@wordpress/components';
import { __experimentalUseProductEntityProp as useProductEntityProp } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { Label } from '../components';

/**
 * @typedef {import('../types.js').ProductEditorBlockContext} ProductEditorBlockContext
 * @typedef {import('../types.js').ProductBasicAttributes} ProductBasicAttributes
 */

/**
 * @typedef {Object} SpecificAttributes
 * @property {import('@wordpress/components').SelectControl.Option} [options=[]] The options to be shown in the select field.
 *
 * @typedef {ProductBasicAttributes & SpecificAttributes} ProductSelectFieldAttributes
 */

/**
 * Custom block for editing a given product data with a select field.
 *
 * @param {Object} props React props.
 * @param {ProductSelectFieldAttributes} props.attributes
 * @param {ProductEditorBlockContext} props.context
 */
export default function Edit( { attributes, context } ) {
	const blockProps = useWooBlockProps( attributes );
	const [ value, setValue ] = useProductEntityProp( attributes.property, {
		postType: context.postType,
	} );

	return (
		<div { ...blockProps }>
			<SelectControl
				label={
					<Label
						label={ attributes.label }
						tooltip={ attributes.tooltip }
					/>
				}
				options={ attributes.options }
				value={ value }
				onChange={ setValue }
			/>
		</div>
	);
}
