export const getAudienceSelectedCountryCodes = ( state ) => {
	return state.mc.audience.location.selected;
};

export const getShippingRates = ( state ) => {
	return state.mc.shipping.rates;
};

export const getSettings = ( state ) => {
	return state.mc.settings;
};

export const getJetpackAccount = ( state ) => {
	return state.mc.accounts.jetpack;
};

export const getGoogleAccount = ( state ) => {
	return state.mc.accounts.google;
};

export const getGoogleMCAccount = ( state ) => {
	return state.mc.accounts.mc;
};

export const getExistingGoogleMCAccounts = ( state ) => {
	return state.mc.accounts.existing_mc;
};
