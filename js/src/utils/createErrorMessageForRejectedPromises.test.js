/**
 * Internal dependencies
 */
import createErrorMessageForRejectedPromises from './createErrorMessageForRejectedPromises';
import createMessageForMultipleErrors from '.~/utils/createMessageForMultipleErrors';

jest.mock( '.~/utils/createMessageForMultipleErrors', () => jest.fn() );

describe( 'createErrorMessageForRejectedPromises', () => {
	const successPromise = () => new Promise( ( resolve ) => resolve( 'OK' ) );
	const rejectedPromise = () =>
		new Promise( ( r, reject ) => reject( 'Failed' ) );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'No rejected Promises', async () => {
		const promises = [
			successPromise(),
			successPromise(),
			successPromise(),
		];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		await createErrorMessageForRejectedPromises( promises, promisesName );

		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith( [], true );
	} );

	it( 'One rejected Promise', async () => {
		const promises = [
			successPromise(),
			rejectedPromise(),
			successPromise(),
		];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		await createErrorMessageForRejectedPromises( promises, promisesName );

		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith(
			[ 'Promise B' ],
			true
		);
	} );

	it( 'Multiple rejected promises', async () => {
		const promises = [
			successPromise(),
			rejectedPromise(),
			rejectedPromise(),
			rejectedPromise(),
		];
		const promisesName = [
			'Promise A',
			'Promise B',
			'Promise C',
			'Promise D',
		];

		await createErrorMessageForRejectedPromises( promises, promisesName );

		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith(
			[ 'Promise B', 'Promise C', 'Promise D' ],
			true
		);
	} );

	it( 'All rejected promises', async () => {
		const promises = [
			rejectedPromise(),
			rejectedPromise(),
			rejectedPromise(),
		];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		await createErrorMessageForRejectedPromises( promises, promisesName );

		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith(
			[ 'Promise A', 'Promise B', 'Promise C' ],
			false
		);
	} );
} );
