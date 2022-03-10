/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import isInShippingRates from '.~/utils/isInShippingRates';
import useShippingRates from './useShippingRates';

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 */

const useSaveShippingRates = () => {
	const { data: oldShippingRates } = useShippingRates();
	const { deleteShippingRates, upsertShippingRates } = useAppDispatch();

	const saveShippingRates = useCallback(
		/**
		 * Saves shipping rates.
		 *
		 * This is done by removing the old shipping rates first,
		 * and then upserting the new shipping rates.
		 *
		 * @param {Array<ShippingRate>} newShippingRates
		 */
		async ( newShippingRates ) => {
			const deleteIds = oldShippingRates
				.filter(
					( oldShippingRate ) =>
						! isInShippingRates( oldShippingRate, newShippingRates )
				)
				.map( ( oldShippingRate ) => oldShippingRate.id );

			if ( deleteIds.length ) {
				await deleteShippingRates( deleteIds );
			}

			if ( newShippingRates.length ) {
				await upsertShippingRates( newShippingRates );
			}
		},
		[ deleteShippingRates, oldShippingRates, upsertShippingRates ]
	);

	return { saveShippingRates };
};

export default useSaveShippingRates;
