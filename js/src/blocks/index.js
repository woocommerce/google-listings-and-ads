/**
 * External dependencies
 */
import { registerProductEditorBlockType } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import SelectFieldEdit from './product-select-field/edit';
import selectFieldMetadata from './product-select-field/block.json';

function registerProductEditorBlock( { name, ...metadata }, Edit ) {
	registerProductEditorBlockType( {
		name,
		metadata,
		settings: { edit: Edit },
	} );
}

registerProductEditorBlock( selectFieldMetadata, SelectFieldEdit );
