/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * Returns the attributes available for mapping
 *
 * @return {Object} The attributes available for mapping
 */
const useMappingAttributes = () => {
	return useAppSelectDispatch( 'getMappingAttributes' );
};

export default useMappingAttributes;
