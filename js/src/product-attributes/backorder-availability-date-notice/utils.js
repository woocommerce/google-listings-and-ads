/**
 * Internal dependencies
 */
import { INVENTORY_PRODUCT_DATA } from './constants';

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
 * Check if the backorder is selected.
 *
 * @return {boolean} True if the backorder is selected, false otherwise.
 */
export const isBackorderSelected = () => {
	const backorders = getFieldValue( '_backorders' );
	const stockStatus = getFieldValue( '_stock_status' );

	return (
		[ 'yes', 'notify' ].includes( backorders ) ||
		stockStatus === 'onbackorder'
	);
};
