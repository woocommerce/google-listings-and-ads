/**
 * Internal dependencies
 */
import { INVENTORY_PRODUCT_DATA, PRODUCT_TYPE_SELECT } from './constants';

/**
 * Get the value of a field from the inventory product data.
 *
 * @param {string} name - The name of the field to get the value of.
 * @return {string} The value of the field.
 */
export const getFieldValue = ( name ) => {
	const select = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } select[name="${ name }"]`
	);

	if ( select ) {
		return select.value;
	}

	const checked = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } input[name="${ name }"]:checked`
	);

	if ( checked ) {
		return checked.value;
	}

	return '';
};

/**
 * Check if the product would be on backorder with the current inventory settings.
 * Mirrors `WC_Product::is_on_backorder()`: with stock management enabled, backorders
 * must be allowed and the stock quantity must be zero or less; otherwise only the
 * stock status counts.
 *
 * @return {boolean} True if the product would be on backorder, false otherwise.
 */
export const isBackorderSelected = () => {
	const manageStock = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } input[name="_manage_stock"]`
	)?.checked;

	if ( ! manageStock ) {
		return getFieldValue( '_stock_status' ) === 'onbackorder';
	}

	const stockQuantity = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } input[name="_stock"]`
	)?.value;

	return (
		[ 'yes', 'notify' ].includes( getFieldValue( '_backorders' ) ) &&
		Number( stockQuantity ) <= 0
	);
};

/**
 * Check if the selected product type supports the availability date.
 *
 * @param {Array<string>} productTypes Product types the availability date applies to.
 * @return {boolean} True if the selected product type is one of `productTypes`.
 */
export const isProductTypeApplicable = ( productTypes ) => {
	const productType = document.querySelector( PRODUCT_TYPE_SELECT )?.value;

	return productTypes.includes( productType );
};
