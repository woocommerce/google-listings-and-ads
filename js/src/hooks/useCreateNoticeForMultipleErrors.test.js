/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useCreateNoticeForMultipleErrors from './useCreateNoticeForMultipleErrors';

const mockCreateNotice = jest.fn().mockName( 'createNotice' );

jest.mock( '.~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: mockCreateNotice,
} ) );

describe( 'useCreateNoticeForMultipleErrors', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'No error messages', () => {
		const errors = [];

		const { result } = renderHook( () =>
			useCreateNoticeForMultipleErrors()
		);

		const { createNoticeForMultipleErrors } = result.current;

		createNoticeForMultipleErrors( errors );

		expect( mockCreateNotice ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'One error message', () => {
		const errors = [ 'Target Audience' ];

		const { result } = renderHook( () =>
			useCreateNoticeForMultipleErrors()
		);

		const { createNoticeForMultipleErrors } = result.current;

		createNoticeForMultipleErrors( errors );

		expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'There are errors in the following actions: Target Audience. Other changes have been saved. Please try again later.'
		);
	} );

	it( 'Multiple errors and it is not partially successful', () => {
		const errors = [ 'Promise A', 'Promise B', 'Promise C' ];
		const isPartiallySuccessful = false;

		const { result } = renderHook( () =>
			useCreateNoticeForMultipleErrors()
		);

		const { createNoticeForMultipleErrors } = result.current;

		createNoticeForMultipleErrors( errors, isPartiallySuccessful );

		expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'There are errors in the following actions: Promise A, Promise B and Promise C.  Please try again later.'
		);
	} );
} );
