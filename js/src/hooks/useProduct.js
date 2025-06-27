/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { PRODUCTS_STORE_NAME } from '@woocommerce/data';

const selectorName = 'getProduct';

/**
 * Custom hook to retrieve product data and resolution status based on a given product ID.
 *
 * @param {string|null} productID - The ID of the product to retrieve. If null, returns default values.
 * @return {Object} An object containing the following properties:
 *   - {Object|null} product: The product data retrieved from the store, or null if no product ID is provided.
 *   - {boolean} hasFinishedResolution: Indicates whether the resolution for the product data has completed.
 */
const useProduct = ( productID ) => {
	return useSelect(
		( select ) => {
			if ( ! productID ) {
				return {
					product: null,
					hasFinishedResolution: true,
				};
			}

			const selector = select( PRODUCTS_STORE_NAME );

			return {
				product: selector[ selectorName ]( productID ),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ productID ]
				),
			};
		},
		[ productID ]
	);
};

export default useProduct;
