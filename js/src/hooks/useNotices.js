/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

/**
 * @typedef {import('@wordpress/notices').Notice} Notice
 */

/**
 * A hook that returns the WP notices
 *
 * @param {string} [storeKey] The store key
 *
 * @return {Array<Notice>} Returns the Notices
 */
const useNotices = ( storeKey = STORE_KEY ) => {
	return useSelect(
		( select ) => {
			const selector = select( storeKey );
			return selector.getNotices();
		},
		[ storeKey ]
	);
};

export default useNotices;
