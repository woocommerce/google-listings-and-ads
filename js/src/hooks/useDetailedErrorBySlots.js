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
 * Custom hook to get error details by slots.
 * It returns the first matching detailed error object for the provided slots.
 *
 * @param {Array<string>} slots - The error slots to check.
 * @return {Object|null} The detailed error object or null if none found.
 */
const useDetailedErrorBySlots = ( slots ) => {
	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const errors = selector[ selectorName ]( slots );

			if ( ! errors || errors.length === 0 ) {
				return null;
			}

			return errors[ 0 ];
		},
		[ slots ]
	);
};

export default useDetailedErrorBySlots;
