/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';

/**
 * Hook for handling pagination based on different pagination keys. Each key maintains their own pagination state
 * Example of usage `const { page, setPage } = usePagination('paginator1')`
 * setPage(2), setPage(3) ...etc
 * Example of usage with different initial page `const { page, setPage } = usePagination('paginator2', 5)`
 *
 * @param {string} paginationId The key used to identify this paginator
 * @param {number} [initialPage=1] The initial page for the paginator
 * @return {{page: number, setPage: Function}} Returns the current page for the selected paginator and a setPage function for update the page.
 */
const usePagination = ( paginationId, initialPage = 1 ) => {
	const [ pagination, setPagination ] = useState( {} );

	const setPage = useCallback(
		( newPage ) => {
			setPagination( ( previousPagination ) => {
				return {
					...previousPagination,
					[ paginationId ]: newPage,
				};
			} );
		},
		[ paginationId ]
	);

	return { page: pagination[ paginationId ] ?? initialPage, setPage };
};
export default usePagination;
