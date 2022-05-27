/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Call `useAppSelectDispatch` with `"getRefundReturnPolicyPageContent"`.
 *
 * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
 */
const useGetRefundReturnPolicyPageContent = () => {
	return useAppSelectDispatch( 'getRefundReturnPolicyPageContent' );
};

export default useGetRefundReturnPolicyPageContent;
