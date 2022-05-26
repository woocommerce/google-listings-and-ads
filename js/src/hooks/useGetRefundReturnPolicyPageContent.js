/**
 * Internal dependencies
 */
 import useAppSelectDispatch from './useAppSelectDispatch';

 /**
  * Call `useAppSelectDispatch` with `"getRefundReturnPolicyPageContent"`.
  *
  * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
  */
 const useGetPaymentGateways = () => {
   return useAppSelectDispatch( 'getRefundReturnPolicyPageContent' );
 };

 export default useGetRefundReturnPolicyPageContent;
