/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @return {Array<{id: string, triggered_at: number}>} Current notifications from the store.
 */
const useNotifications = () => {
	return useSelect(
		( select ) => select( STORE_KEY ).getNotifications(),
		[]
	);
};

export default useNotifications;
