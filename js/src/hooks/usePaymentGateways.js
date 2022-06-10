/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get Payment Gateways from API. Returns `{ hasFinishedResolution, data, invalidateResolution }`.
 *
 * `data` is an object of payment gateways mapping. e.g.:
 *
 * ```json
 * {
 *		"payment_gateways": {
 * 			"id": "wc_custom_pg",
 *		        "title": "Custom Payment Gateway",
 *		        "method_description":
 *				"Description of the payment gateway",
 *		}
 *
 * }
 * ```
 */
const usePaymentGateways = () => {
	return useAppSelectDispatch( 'getPaymentGateways' );
};

export default usePaymentGateways;
