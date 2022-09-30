/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * Returns the Attribute Mapping Rules
 *
 * @return {Object} The rules
 */
const useMappingRules = () => {
	return useAppSelectDispatch( 'getMappingRules' );
};

export default useMappingRules;
