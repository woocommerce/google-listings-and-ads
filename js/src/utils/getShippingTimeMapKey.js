const getShippingTimeMapKey = ( time, maxtime ) => {
	return `${ time }-${ maxtime }`;
};

export default getShippingTimeMapKey;
