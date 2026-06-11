/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

const STORE_NAME = 'woocommerce/marketing-notifications-system';

/**
 * @return {Array<Object>} Notifications from the shared marketing notifications store.
 */
const useNotifications = () => {
	return useSelect( ( select ) => {
		return select( STORE_NAME ).getNotifications();
	}, [] );
};

export default useNotifications;
