/**
 * Internal dependencies
 */
import createErrorMessageForRejectedPromises from './createErrorMessageForRejectedPromises';

describe( 'createErrorMessageForRejectedPromises', () => {
	const successPromise = new Promise( ( resolve ) => resolve( 'OK' ) );
	const rejectedPromise = new Promise( ( r, reject ) => reject( 'Failed' ) );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'No rejected Promises', async () => {
		const promises = [ successPromise, successPromise, successPromise ];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const rejectedMessages = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toBeNull();
	} );

	it( 'One rejected Promise', async () => {
		const promises = [ successPromise, rejectedPromise, successPromise ];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const rejectedMessages = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toEqual(
			'There is an error in the following action: Promise B. Other changes have been saved. Please try again later.'
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

		const rejectedMessages = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toEqual(
			'There are errors in the following actions: Promise B, Promise C and Promise D. Other changes have been saved. Please try again later.'
		);
	} );

	it( 'All rejected promises', async () => {
		const promises = [ rejectedPromise, rejectedPromise, rejectedPromise ];
		const promisesName = [ 'Promise A', 'Promise B', 'Promise C' ];

		const rejectedMessages = await createErrorMessageForRejectedPromises(
			promises,
			promisesName
		);

		expect( rejectedMessages ).toEqual(
			'There are errors in the following actions: Promise A, Promise B and Promise C.  Please try again later.'
		);
	} );
} );
