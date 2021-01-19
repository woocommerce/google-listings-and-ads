export const selectExample1 = ( state ) => {
	return state.installedPlugins;
};

export const getAudienceSelectedCountries = ( state ) => {
	return state.mc.audience.location.selected;
};
