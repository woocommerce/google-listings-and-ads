/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';

/**
 * Get a memoized URL query object.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
export default function useUrlQuery() {
	const query = getQuery();
	const queryRefValue = useIsEqualRefValue( query );

	return queryRefValue;
}
