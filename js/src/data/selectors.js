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
