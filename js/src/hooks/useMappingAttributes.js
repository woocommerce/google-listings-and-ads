/**
 * Returns the attributes available for mapping
 *
 * @return {Object} The attributes available for mapping
 */
const useMappingAttributes = () => {
	// Todo: Replace with API data after approval.

	return {
		data: [
			{ id: '', label: 'Select one attribute' },
			{ id: 'adult', label: 'Adult', enum: true },
			{ id: 'brand', label: 'Brand', enum: false },
		],
	};
};

export default useMappingAttributes;
