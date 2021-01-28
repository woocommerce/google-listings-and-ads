/**
 * Internal dependencies
 */
import {
	fetchShippingRates,
	fetchSettings,
	fetchJetpackAccount,
} from './actions';

export function* getShippingRates() {
	yield fetchShippingRates();
}

export function* getSettings() {
	yield fetchSettings();
}

export function* getJetpackAccount() {
	yield fetchJetpackAccount();
}
