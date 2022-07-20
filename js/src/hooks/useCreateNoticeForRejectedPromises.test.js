/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useCreateNoticeForRejectedPromises from './useCreateNoticeForRejectedPromises';

const mockCreateNotice = jest.fn().mockName( 'createNotice' );

jest.mock( '.~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: mockCreateNotice,
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
		expect( mockCreateNotice ).toHaveBeenCalledTimes( 0 );
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
		expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'There are errors in the following actions: Promise B. Other changes have been saved. Please try again later.'
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
		expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'There are errors in the following actions: Promise B, Promise C and Promise D. Other changes have been saved. Please try again later.'
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
		expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'There are errors in the following actions: Promise A, Promise B and Promise C.  Please try again later.'
		);
	} );
} );
