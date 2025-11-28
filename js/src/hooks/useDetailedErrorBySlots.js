/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getDetailedErrorBySlots';

const useDetailedErrorBySlots = ( errorSlots ) => {
	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const errors = selector[ selectorName ]( errorSlots );

			if ( ! errors || errors.length === 0 ) {
				return null;
			}

			return errors[ 0 ];
		},
		[ errorSlots ]
	);
};

export default useDetailedErrorBySlots;
