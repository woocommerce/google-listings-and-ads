/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

const useDispatchOptions = () => {
	return useDispatch( OPTIONS_STORE_NAME );
};

export default useDispatchOptions;
