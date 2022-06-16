/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useUnmountableNotice from './useUnmountableNotice';

const mockCreateNotice = jest.fn();
const mockRemoveNotice = jest.fn();

jest.mock( '@wordpress/data', () => {
	return {
		dispatch: jest.fn().mockImplementation( () => ( {
			createNotice: mockCreateNotice,
			removeNotice: mockRemoveNotice,
		} ) ),
	};
} );

describe( 'useUnmountableNotice', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Should return function with createNotice', () => {
		const { result } = renderHook( () =>
			useUnmountableNotice( 'success', 'test label' )
		);

		expect( mockRemoveNotice ).toHaveBeenCalledTimes( 0 );

		expect( result.current ).toEqual( expect.any( Function ) );

		result.current();

		expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'success',
			'test label',
			{ id: expect.any( String ) }
		);
	} );

	test( 'Should remove notice when the hook is unmounted', () => {
		const { result, unmount } = renderHook( () =>
			useUnmountableNotice( 'success', 'test label' )
		);

		unmount();

		expect( mockRemoveNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateNotice ).toHaveBeenCalledTimes( 0 );
		expect( result.current ).toEqual( expect.any( Function ) );
	} );

	test( 'Should remove with custom ID', () => {
		const myID = 'my_custom_id';
		const { result, unmount } = renderHook( () =>
			useUnmountableNotice( 'success', 'test label', {
				id: myID,
			} )
		);

		unmount();

		expect( mockRemoveNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockRemoveNotice ).toHaveBeenCalledWith( myID );
		expect( mockCreateNotice ).toHaveBeenCalledTimes( 0 );
		expect( result.current ).toEqual( expect.any( Function ) );
	} );
} );
