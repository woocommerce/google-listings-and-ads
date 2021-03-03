/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';

const useDispatchCoreNotices = () => {
	return useDispatch( 'core/notices' );
};

export default useDispatchCoreNotices;
