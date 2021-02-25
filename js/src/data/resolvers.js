/**
 * Internal dependencies
 */
import {
	fetchShippingRates,
	fetchSettings,
	fetchCountries,
	fetchTargetAudience,
} from './actions';

export function* getShippingRates() {
	yield fetchShippingRates();
}

export function* getSettings() {
	yield fetchSettings();
}

export function* getCountries() {
	yield fetchCountries();
}

export function* getTargetAudience() {
	yield fetchTargetAudience();
}
