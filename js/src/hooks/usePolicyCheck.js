/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get policy detail info from calling `useAppSelectDispatch` with `getPolicyCheck`.
 * Returns `{ hasFinishedResolution, data, invalidateResolution }`.
 *
 * `data` is an object of policy check mapping. e.g.:
 *
 * ```json
 * {
 *	'policy_check' =>	[
 *				'allowed_countries'    	=> true,
 *                              'robots_restriction'    => false,
 *                              'page_not_found_error'  => false,
 *                              'page_restricts'    	=> false,
 *				'store_ssl'         	=> true,
 *				'payment_gateways'  	=> true,
 *				'refund_returns' 	=> true,
 *				]
 * }
 * ```
 */
const usePolicyCheck = () => {
	return useAppSelectDispatch( 'getPolicyCheck' );
};

export default usePolicyCheck;
