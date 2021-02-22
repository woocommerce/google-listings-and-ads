/**
 * Internal dependencies
 */
import {
	fetchShippingRates,
	fetchSettings,
	fetchJetpackAccount,
	fetchGoogleAccount,
	fetchGoogleMCAccount,
	fetchExistingGoogleMCAccounts,
	fetchCountries,
	fetchTargetAudience,
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

export function* getGoogleMCAccount() {
	yield fetchGoogleMCAccount();
}

export function* getExistingGoogleMCAccounts() {
	yield fetchExistingGoogleMCAccounts();
}

export function* getCountries() {
	yield fetchCountries();
}

export function* getTargetAudience() {
	yield fetchTargetAudience();
}
