/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * @typedef {import('.~/hooks/types.js').StoreAddress} StoreAddress
 */

/**
 * Maps the missing fields of store address to corresponding error messages.
 *
 * @param {StoreAddress} storeAddress [description]
 * @return {string[]} Error massages of store address.
 */
export default function mapStoreAddressErrors( storeAddress ) {
	// The sources of the possible field names:
	// - https://github.com/woocommerce/google-listings-and-ads/blob/2.5.0/src/API/Google/Settings.php#L322-L339
	// - https://github.com/woocommerce/woocommerce/blob/7.9.0/plugins/woocommerce/includes/class-wc-countries.php#L841-L1654
	const fieldNameDict = {
		address_1: _x(
			'address line',
			'The field name of the address line in store address',
			'google-listings-and-ads'
		),
		city: _x(
			'city',
			'The field name of the city in store address',
			'google-listings-and-ads'
		),
		country: _x(
			'country/state',
			'The field name of the country in store address',
			'google-listings-and-ads'
		),
		postcode: _x(
			'postcode/zip',
			'The field name of the postcode in store address',
			'google-listings-and-ads'
		),
	};

	return storeAddress.missingRequiredFields.map( ( field ) => {
		const fieldName = fieldNameDict[ field ] || field;
		return sprintf(
			// translators: %s: The missing field name of store address.
			__(
				'The %s of store address is required.',
				'google-listings-and-ads'
			),
			fieldName
		);
	} );
}
