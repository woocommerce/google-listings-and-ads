/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get Merchant Center setup info.
 */
const useEnhancedConversionsStatus = () => {
	return useAppSelectDispatch( 'getEnhancedConversionsStatus' );
};

export default useEnhancedConversionsStatus;
