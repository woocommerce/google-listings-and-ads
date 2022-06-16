/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useEffectRemoveNotice from './useEffectRemoveNotice';
import useNotices from '.~/hooks/useNotices';
import { NOTICES_KEY } from '.~/data/constants';

const mockRemoveNotice = jest.fn();

jest.mock( '.~/hooks/useNotices', () => {
	return jest.fn();
} );

jest.mock( '@wordpress/data', () => {
	return {
		dispatch: jest.fn().mockImplementation( () => ( {
			removeNotice: mockRemoveNotice,
		} ) ),
	};
} );

describe( 'useEffectRemoveNotice', () => {
	const testLabel = 'test_label';
	const notice = {
		id: 1,
		content: testLabel,
	};

	beforeEach( () => {
		jest.clearAllMocks();
		useNotices.mockImplementation( () => [ notice ] );
	} );

	test( 'Should return with the notice', () => {
		const { result } = renderHook( () =>
			useEffectRemoveNotice( testLabel )
		);

		expect( mockRemoveNotice ).toHaveBeenCalledTimes( 0 );
		expect( useNotices ).toHaveBeenCalledWith( NOTICES_KEY );

		expect( result.current ).toEqual( notice );
	} );

	test( 'Should return with null if the notice is not found', () => {
		useNotices.mockImplementation( () => [
			{ ...notice, content: 'different_labels' },
		] );

		const { result } = renderHook( () =>
			useEffectRemoveNotice( testLabel )
		);

		expect( mockRemoveNotice ).toHaveBeenCalledTimes( 0 );
		expect( useNotices ).toHaveBeenCalledWith( NOTICES_KEY );

		expect( result.current ).toEqual( null );
	} );

	test( 'Should remove notice when the hook is unmounted', () => {
		const { result, unmount } = renderHook( () =>
			useEffectRemoveNotice( testLabel )
		);

		unmount();

		expect( mockRemoveNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockRemoveNotice ).toHaveBeenCalledWith( notice.id );
		expect( result.current ).toEqual( notice );
	} );
} );
