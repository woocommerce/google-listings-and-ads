/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import getDifferentShippingRates from '~/utils/getDifferentShippingRates';
import getDeletedShippingRates from '~/utils/getDeletedShippingRates';
import useShippingRates from './useShippingRates';

/**
 * @typedef { import("~/data/actions").ShippingRate } ShippingRate
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
		 * @param {Array<ShippingRate>} shippingRatesToSave
		 * @param {Array<string>} [excludedCountryCodes=[]] Country codes to
		 *   skip entirely — no deletes or upserts will be applied to them.
		 *   Use this when the caller only manages a subset of markets (e.g. the
		 *   primary market) so that rates belonging to other markets are never
		 *   accidentally deleted.
		 * @throws Will throw an error if any request failed.
		 */
		async ( shippingRatesToSave, excludedCountryCodes = [] ) => {
			const excluded = new Set( excludedCountryCodes );

			// Restrict both sides to the countries this call manages.
			// Countries in `excludedCountryCodes` belong to other markets
			// and must not be deleted or upserted.
			const managedNewRates = shippingRatesToSave.filter(
				( shippingRate ) => ! excluded.has( shippingRate.country )
			);
			const managedOldRates = oldShippingRates.filter(
				( shippingRate ) => ! excluded.has( shippingRate.country )
			);

			const deleteIds = getDeleteIds( managedNewRates, managedOldRates );

			if ( deleteIds.length ) {
				await deleteShippingRates( deleteIds );
			}

			const diffShippingRates = getDifferentShippingRates(
				managedNewRates,
				managedOldRates
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
