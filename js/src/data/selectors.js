export const getAudienceSelectedCountryCodes = ( state ) => {
	return state.mc.audience.location.selected;
};

export const getShippingRates = ( state ) => {
	return state.mc.shipping.rates;
};

export const getSettings = ( state ) => {
	return state.mc.settings;
};

export const getCountries = ( state ) => {
	return state.mc.countries;
};

export const getTargetAudience = ( state ) => {
	return state.mc.target_audience;
};
