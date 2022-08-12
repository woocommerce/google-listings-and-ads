/**
 * Internal dependencies
 */
import createErrorMessageForRejectedPromises from './createErrorMessageForRejectedPromises';
import createMessageForMultipleErrors from '.~/utils/createMessageForMultipleErrors';

jest.mock( '.~/utils/createMessageForMultipleErrors', () => {
	const originalModule = jest.requireActual(
		'.~/utils/createMessageForMultipleErrors'
	);
	return jest.fn( originalModule.default );
} );

describe( 'createErrorMessageForRejectedPromises', () => {
	const successPromise = () => new Promise( ( resolve ) => resolve( 'OK' ) );
	const rejectedPromise = () =>
		new Promise( ( r, reject ) => reject( 'Failed' ) );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'Called with no rejected promises, calls `createMessageForMultipleErrors` with an empty array and `true`, and returns `null`', async () => {
		const promises = [
			successPromise(),
			successPromise(),
			successPromise(),
		];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const message = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( message ).toBeNull();
		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith( [], true );
	} );

	it( 'Called with one rejected promise, calls `createMessageForMultipleErrors` with its name, and `true`, and returns a message indicates it contains one error and partially successful results', async () => {
		const promises = [
			successPromise(),
			rejectedPromise(),
			successPromise(),
		];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const message = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( message ).toMatchSnapshot();
		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith(
			[ 'Promise B' ],
			true
		);
	} );

	it( 'Called with multiple rejected promises, calls `createMessageForMultipleErrors` with their names, and `true`, and returns a message indicates it contains multiple errors and partially successful results', async () => {
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

		const message = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( message ).toMatchSnapshot();
		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith(
			[ 'Promise B', 'Promise C', 'Promise D' ],
			true
		);
	} );

	it( 'Called with all rejected promises, calls `createMessageForMultipleErrors` with their names, and `false`, and returns a message indicates it contains multiple errors without successful results', async () => {
		const promises = [
			rejectedPromise(),
			rejectedPromise(),
			rejectedPromise(),
		];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const message = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( message ).toMatchSnapshot();
		expect( createMessageForMultipleErrors ).toBeCalledTimes( 1 );
		expect( createMessageForMultipleErrors ).toBeCalledWith(
			[ 'Promise A', 'Promise B', 'Promise C' ],
			false
		);
	} );
} );
