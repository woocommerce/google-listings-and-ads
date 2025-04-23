/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';

/**
 * ChangePrice component.
 *
 * Placeholder component.
 *
 * @param {Object} props - Component properties.
 * @param {number} props.productID - The ID of the product for which the price is being changed.
 * @param {string} [props.label='Change price'] - The label text for the button. Defaults to 'Change price'.
 * @return {JSX.Element} The rendered AppButton component.
 */
const ChangePrice = ( {
	productID,
	label = __( 'Change price', 'google-listings-and-ads' ),
} ) => {
	return <AppButton id={ productID }>{ label }</AppButton>;
};

export default ChangePrice;
