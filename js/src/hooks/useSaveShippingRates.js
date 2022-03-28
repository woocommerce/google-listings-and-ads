/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import getDifferentShippingRates from '.~/utils/getDifferentShippingRates';
import getDeletedShippingRates from '.~/utils/getDeletedShippingRates';
import useShippingRates from './useShippingRates';

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 */

const getDeleteIds = ( newShippingRates, oldShippingRates ) => {
	const deletedShippingRates = getDeletedShippingRates(
		newShippingRates,
		oldShippingRates
	);

	return deletedShippingRates.map( ( shippingRate ) => shippingRate.id );
};

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
			const deleteIds = getDeleteIds(
				newShippingRates,
				oldShippingRates
			);

			if ( deleteIds.length ) {
				await deleteShippingRates( deleteIds );
			}

			const diffShippingRates = getDifferentShippingRates(
				newShippingRates,
				oldShippingRates
			);
			if ( diffShippingRates.length ) {
				await upsertShippingRates( diffShippingRates );
			}
		},
		[ deleteShippingRates, oldShippingRates, upsertShippingRates ]
	);

	return { saveShippingRates };
};

export default useSaveShippingRates;
