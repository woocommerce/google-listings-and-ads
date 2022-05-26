/**
 * Internal dependencies
 */
 import useAppSelectDispatch from './useAppSelectDispatch';

 /**
  * Call `useAppSelectDispatch` with `"getPaymentGateways"`.
  *
  * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
  */
 const useGetPaymentGateways = () => {
	 return useAppSelectDispatch( 'getPaymentGateways' );
 };

 export default useGetPaymentGateways;
