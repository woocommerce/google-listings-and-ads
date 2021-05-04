/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get Merchant Center setup info.
 */
const useMCSetup = () => {
	return useAppSelectDispatch( 'getMCSetup' );
};

export default useMCSetup;
