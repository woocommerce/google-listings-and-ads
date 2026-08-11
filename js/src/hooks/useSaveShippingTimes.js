/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useShippingTimes from './useShippingTimes';
import getDeletedShippingTimes from '~/utils/getDeletedShippingTimes';
import getDifferentShippingTimes from '~/utils/getDifferentShippingTimes';
import getShippingTimesGroups from '~/utils/getShippingTimesGroups';

/**
 * @typedef { import("~/data/actions").ShippingTime } ShippingTime
 */

/**
 * Get the country codes of the deleted shipping times.
 *
 * Deleted shipping times are those that exist in `oldShippingTimes` but not in `newShippingTimes`.
 *
 * @param {Array<ShippingTime>} newShippingTimes New shipping times.
 * @param {Array<ShippingTime>} oldShippingTimes Old shipping times.
 * @return {Array<string>} Array of country codes.
 */
const getDeletedCountryCodes = ( newShippingTimes, oldShippingTimes ) => {
	const deletedShippingTimes = getDeletedShippingTimes(
		newShippingTimes,
		oldShippingTimes
	);

	return deletedShippingTimes.map(
		( shippingTime ) => shippingTime.countryCode
	);
};

const useSaveShippingTimes = () => {
	const { data: oldShippingTimes } = useShippingTimes();
	const { deleteShippingTimes, upsertShippingTimes } = useAppDispatch();

	const saveShippingTimes = useCallback(
		/**
		 * Saves shipping times.
		 *
		 * This is done by removing the old shipping times first,
		 * and then upserting the new shipping times.
		 *
		 * @param {Array<ShippingTime>} shippingTimesToSave
		 * @param {Array<string>} [excludedCountryCodes=[]] Country codes to
		 *   skip entirely — no deletes or upserts will be applied to them.
		 *   Use this when the caller only manages a subset of markets (e.g. the
		 *   primary market) so that times belonging to other markets are never
		 *   accidentally deleted.
		 * @throws Will throw an error if any request failed.
		 */
		async ( shippingTimesToSave, excludedCountryCodes = [] ) => {
			const excluded = new Set( excludedCountryCodes );

			// Restrict both sides to the countries this call manages.
			// Countries in `excludedCountryCodes` belong to other markets
			// and must not be deleted or upserted.
			const managedNewTimes = shippingTimesToSave.filter(
				( shippingTime ) => ! excluded.has( shippingTime.countryCode )
			);
			const managedOldTimes = oldShippingTimes.filter(
				( shippingTime ) => ! excluded.has( shippingTime.countryCode )
			);

			const deletedCountryCodes = getDeletedCountryCodes(
				managedNewTimes,
				managedOldTimes
			);

			if ( deletedCountryCodes.length ) {
				await deleteShippingTimes( deletedCountryCodes );
			}

			const diffShippingTimes = getDifferentShippingTimes(
				managedNewTimes,
				managedOldTimes
			);
			if ( diffShippingTimes.length ) {
				const promises = getShippingTimesGroups(
					diffShippingTimes
				).map( ( group ) => {
					return upsertShippingTimes( group );
				} );

				await Promise.all( promises );
			}
		},
		[ deleteShippingTimes, oldShippingTimes, upsertShippingTimes ]
	);

	return { saveShippingTimes };
};

export default useSaveShippingTimes;
