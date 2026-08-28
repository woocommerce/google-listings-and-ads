/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Renders a checkbox with options to choose whether offer free shipping
 * for orders over a certain price.
 *
 * @param {Object} props React props.
 * @param {boolean} props.value The value of whether offer free shipping.
 * @param {(nextValue: boolean) => void} props.onChange Callback called with the next value of the selected option.
 */
const OfferFreeShippingCheckbox = ( { value, onChange } ) => {
	return (
		<CheckboxControl
			checked={ value }
			label={ __(
				'Free shipping over a specific order value',
				'google-listings-and-ads'
			) }
			onChange={ onChange }
		/>
	);
};

export default OfferFreeShippingCheckbox;
