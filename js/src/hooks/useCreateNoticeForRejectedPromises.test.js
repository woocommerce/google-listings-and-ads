/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useCreateNoticeForRejectedPromises from './useCreateNoticeForRejectedPromises';

const mockCreateNoticeForMultipleErrors = jest.fn().mockName( 'createNotice' );

jest.mock( '.~/hooks/useCreateNoticeForMultipleErrors', () => () => ( {
	createNoticeForMultipleErrors: mockCreateNoticeForMultipleErrors,
} ) );

describe( 'useCreateNoticeForRejectedPromises', () => {
	const successPromise = new Promise( ( resolve ) => resolve( 'OK' ) );
	const rejectedPromise = new Promise( ( r, reject ) => reject( 'Failed' ) );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'No rejected Promises', async () => {
		const promises = [ successPromise, successPromise, successPromise ];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const { result } = renderHook( () =>
			useCreateNoticeForRejectedPromises()
		);

		const { createNoticeForRejectedPromises } = result.current;

		const rejectedMessages = await createNoticeForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toEqual( [] );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledWith(
			[],
			true
		);
	} );

	it( 'One rejected Promise', async () => {
		const promises = [ successPromise, rejectedPromise, successPromise ];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const { result } = renderHook( () =>
			useCreateNoticeForRejectedPromises()
		);

		const { createNoticeForRejectedPromises } = result.current;

		const rejectedMessages = await createNoticeForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toEqual( [ 'Promise B' ] );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledWith(
			[ 'Promise B' ],
			true
		);
	} );

	it( 'Multiple rejected promises', async () => {
		const promises = [
			successPromise,
			rejectedPromise,
			rejectedPromise,
			rejectedPromise,
		];
		const promisesName = [
			'Promise A',
			'Promise B',
			'Promise C',
			'Promise D',
		];

		const { result } = renderHook( () =>
			useCreateNoticeForRejectedPromises()
		);

		const { createNoticeForRejectedPromises } = result.current;

		const rejectedMessages = await createNoticeForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toEqual( [
			'Promise B',
			'Promise C',
			'Promise D',
		] );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledWith(
			[ 'Promise B', 'Promise C', 'Promise D' ],
			true
		);
	} );

	it( 'All rejected promises', async () => {
		const promises = [ rejectedPromise, rejectedPromise, rejectedPromise ];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const { result } = renderHook( () =>
			useCreateNoticeForRejectedPromises()
		);

		const { createNoticeForRejectedPromises } = result.current;

		const rejectedMessages = await createNoticeForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toEqual( [
			'Promise A',
			'Promise B',
			'Promise C',
		] );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNoticeForMultipleErrors ).toHaveBeenCalledWith(
			[ 'Promise A', 'Promise B', 'Promise C' ],
			false
		);
	} );
} );
