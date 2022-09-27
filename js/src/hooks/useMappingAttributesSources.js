/**
 * Returns available source data based on an attribute
 *
 * @param {string} attributeKey The key for the attribute we want to get the sources
 * @return {Object} Object with ths available sources
 */
const useMappingAttributesSources = ( attributeKey ) => {
	// Todo: Replace with API data after approval.

	if ( attributeKey === 'brand' ) {
		return {
			data: [
				{ value: 'disabled:tax', label: 'Product taxonomies' },
				{ value: 'tax:tax_1', label: 'Taxonomy 1' },
				{ value: 'disabled:field', label: 'Product fields' },
				{ value: 'field:product_field_1', label: 'Product field 1' },
				{ value: 'field:product_field_2', label: 'Product field 2' },
			],
		};
	}

	if ( attributeKey === 'adult' ) {
		return {
			data: [
				{ value: 'yes', label: 'Yes' },
				{ value: 'no', label: 'No' },
			],
		};
	}

	return {
		data: [],
	};
};

export default useMappingAttributesSources;
