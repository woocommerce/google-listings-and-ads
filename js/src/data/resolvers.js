/**
 * Internal dependencies
 */
import { fetchShippingRates, fetchSettings } from './actions';

export function* getShippingRates() {
	yield fetchShippingRates();
}

export function* getSettings() {
	yield fetchSettings();
}
