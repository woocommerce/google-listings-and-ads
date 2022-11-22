/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * Returns available source data based on an attribute
 *
 * @param {string} attributeKey The key for the attribute we want to get the sources
 * @return {Object} Object with ths available sources
 */
const useMappingAttributesSources = ( attributeKey ) => {
	return useAppSelectDispatch( 'getMappingSources', attributeKey );
};

export default useMappingAttributesSources;
