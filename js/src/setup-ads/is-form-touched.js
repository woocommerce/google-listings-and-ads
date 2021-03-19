const isFormTouched = ( formProps ) => {
	const { touched } = formProps;
	return Object.entries( touched ).length >= 1;
};

export default isFormTouched;
