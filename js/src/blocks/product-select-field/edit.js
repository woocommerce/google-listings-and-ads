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
