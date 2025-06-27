/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { getQuery, addHistoryListener } from '@woocommerce/navigation';

/**
 * Hook to get the current URL query and ignore query changes after unmounting.
 *
 * This implementation differs from the one in `@woocommerce/navigation` in
 * that it uses the current query as the initial state rather than an empty
 * object.
 *
 * @return {Record<string, string>} Current query object.
 */
export default function useQuery() {
	const [ query, setQuery ] = useState( getQuery );

	useEffect( () => {
		return addHistoryListener( () => {
			// Adding a delay is because `addHistoryListener` might incorrectly
			// dispatch events before applying changes to `history`. Calling
			// `getQuery` immediately will get the query before the change.
			// Ref:
			// - https://github.com/woocommerce/woocommerce/blob/9.1.0/packages/js/navigation/src/index.js#L173-L186
			// - https://github.com/woocommerce/woocommerce/blob/9.1.0/packages/js/navigation/src/index.js#L184-L185
			setTimeout( () => setQuery( getQuery() ) );
		} );
	}, [] );

	return query;
}
