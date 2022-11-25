/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * Returns the Attribute Mapping Rules
 *
 * @param {Object} pagination The pagination for the request.
 * @param {number} [pagination.page] The page number to query
 * @param {number} [pagination.perPage] The number of items per page
 * @return {Object} The rules together with the total and pages.
 */
const useMappingRules = ( { page = 1, perPage = 10 } ) => {
	return useAppSelectDispatch( 'getMappingRules', { page, perPage } );
};

export default useMappingRules;
