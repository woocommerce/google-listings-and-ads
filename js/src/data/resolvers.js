/**
 * Internal dependencies
 */
import { fetchShippingRates } from './actions';

export function* getShippingRates() {
	yield fetchShippingRates();
}
