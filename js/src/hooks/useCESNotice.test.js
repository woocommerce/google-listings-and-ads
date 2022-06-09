/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useCESNotice from './useCESNotice';
import useUnmountableNotice from './useUnmountableNotice';

const mockOnClickCallBack = jest.fn();
const mockOnNoticeDismissedCallback = jest.fn();
const mockUnmountableNotice = jest.fn();

jest.mock( '.~/hooks/useUnmountableNotice', () => {
	return jest.fn();
} );

const Icon = <span>My Icon</span>;

describe( 'useCESNotice', () => {
	test( 'Should return function with the CES values', () => {
		useUnmountableNotice.mockImplementation(
			( type, label, options ) => () =>
				mockUnmountableNotice( type, label, options )
		);

		const { result } = renderHook( () =>
			useCESNotice(
				'my label',
				Icon,
				mockOnClickCallBack,
				mockOnNoticeDismissedCallback
			)
		);

		result.current();

		expect( mockUnmountableNotice ).toHaveBeenCalledTimes( 1 );

		const type = mockUnmountableNotice.mock.calls[ 0 ][ 0 ];
		const label = mockUnmountableNotice.mock.calls[ 0 ][ 1 ];
		const options = mockUnmountableNotice.mock.calls[ 0 ][ 2 ];

		expect( type ).toBe( 'success' );
		expect( label ).toBe( 'my label' );
		expect( options.icon ).toBe( Icon );
		expect( options.onDismiss ).toBe( mockOnNoticeDismissedCallback );
		expect( options.actions[ 0 ].onClick ).toBe( mockOnClickCallBack );
	} );
} );
