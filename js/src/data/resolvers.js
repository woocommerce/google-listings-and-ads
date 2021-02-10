/**
 * Internal dependencies
 */
import {
	fetchShippingRates,
	fetchSettings,
	fetchJetpackAccount,
	fetchGoogleAccount,
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

export function* getGoogleAccount() {
	yield fetchGoogleAccount();
}
