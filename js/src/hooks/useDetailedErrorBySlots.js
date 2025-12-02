/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getDetailedErrorBySlots';

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
