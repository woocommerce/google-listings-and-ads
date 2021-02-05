export const getAudienceSelectedCountryCodes = ( state ) => {
	return state.mc.audience.location.selected;
};

export const getShippingRates = ( state ) => {
	return state.mc.shipping.rates;
};
