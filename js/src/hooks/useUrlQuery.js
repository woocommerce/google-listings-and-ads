/**
 * External dependencies
 */
import isEqual from 'lodash/isEqual';
import { getQuery } from '@woocommerce/navigation';
import { useRef } from '@wordpress/element';

/**
 * Get a memoized URL query object.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
export default function useUrlQuery() {
	const query = getQuery();
	const queryRef = useRef( query );

	if ( ! isEqual( queryRef.current, query ) ) {
		queryRef.current = query;
	}

	return queryRef.current;
}
