/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import usePagination from '.~/hooks/usePagination';

describe( 'usePagination', () => {
	it( 'returns the correct default page per each paginator key', () => {
		const { result, rerender } = renderHook(
			( { key, defaultPage } ) => usePagination( key, defaultPage ),
			{
				initialProps: {
					key: 'paginator1',
				},
			}
		);

		expect( result.current.page ).toBe( 1 );

		rerender( { key: 'paginator2', defaultPage: 8 } );

		expect( result.current.page ).toBe( 8 );
	} );

	it( 'can set the page of each paginator per each paginator key', async () => {
		const { result, rerender } = renderHook(
			( { key, defaultPage } ) => usePagination( key, defaultPage ),
			{
				initialProps: {
					key: 'paginator1',
				},
			}
		);

		expect( result.current.page ).toBe( 1 );

		act( () => {
			result.current.setPage( 2 );
		} );

		expect( result.current.page ).toBe( 2 );

		rerender( { key: 'paginator2' } );

		expect( result.current.page ).toBe( 1 );

		act( () => {
			result.current.setPage( 4 );
		} );

		expect( result.current.page ).toBe( 4 );

		rerender( { key: 'paginator1' } );

		expect( result.current.page ).toBe( 2 );
	} );
} );
