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
		 * @param {Array<ShippingTime>} newShippingTimes
		 * @throws Will throw an error if any request failed.
		 */
		async ( newShippingTimes ) => {
			const deletedCountryCodes = getDeletedCountryCodes(
				newShippingTimes,
				oldShippingTimes
			);

			if ( deletedCountryCodes.length ) {
				await deleteShippingTimes( deletedCountryCodes );
			}

			const diffShippingTimes = getDifferentShippingTimes(
				newShippingTimes,
				oldShippingTimes
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
