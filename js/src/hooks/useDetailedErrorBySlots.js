/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getDetailedErrorBySlots';

/**
 * Custom hook to get the first error per slot.
 *
 * For the provided list of slots, it returns an array where each element is the
 * first error belonging to that slot (0th index for the slot). If a slot has
 * no error, it is omitted from the returned array.
 *
 * @param {Array<string>} slots - The error slots to check.
 * @return {Array<Object>} Array of first errors per slot. Empty if none.
 */
const useDetailedErrorBySlots = ( slots ) => {
	return useSelect(
		( select ) => {
			// Guard against undefined or non-array inputs.
			const safeSlots = Array.isArray( slots ) ? slots : [];
			const selector = select( STORE_KEY );
			const allErrors = selector[ selectorName ]( safeSlots ) || [];

			const firstErrorsPerSlot = safeSlots.reduce( ( acc, slot ) => {
				const firstForSlot = allErrors.find(
					( err ) => err && err.slot === slot
				);
				if ( firstForSlot ) {
					acc.push( firstForSlot );
				}
				return acc;
			}, [] );

			return firstErrorsPerSlot;
		},
		[ slots ]
	);
};

export default useDetailedErrorBySlots;
