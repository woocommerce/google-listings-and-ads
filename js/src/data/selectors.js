export const getShippingRates = ( state ) => {
	return state.mc.shipping.rates;
};

export const getShippingTimes = ( state ) => {
	return state.mc.shipping.times;
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

export const getGoogleAdsAccount = ( state ) => {
	return state.mc.accounts.ads;
};

export const getExistingGoogleAdsAccounts = ( state ) => {
	return state.mc.accounts.existing_ads;
};

export const getCountries = ( state ) => {
	return state.mc.countries;
};

export const getTargetAudience = ( state ) => {
	return state.mc.target_audience;
};
