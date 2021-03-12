/**
 * Internal dependencies
 */
import {
	fetchShippingRates,
	fetchShippingTimes,
	fetchSettings,
	fetchJetpackAccount,
	fetchGoogleAccount,
	fetchGoogleMCAccount,
	fetchExistingGoogleMCAccounts,
	fetchGoogleAdsAccount,
	fetchExistingGoogleAdsAccounts,
	fetchCountries,
	fetchTargetAudience,
} from './actions';

export function* getShippingRates() {
	yield fetchShippingRates();
}

export function* getShippingTimes() {
	yield fetchShippingTimes();
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

export function* getGoogleAdsAccount() {
	yield fetchGoogleAdsAccount();
}

export function* getExistingGoogleAdsAccounts() {
	yield fetchExistingGoogleAdsAccounts();
}

export function* getCountries() {
	yield fetchCountries();
}

export function* getTargetAudience() {
	yield fetchTargetAudience();
}
