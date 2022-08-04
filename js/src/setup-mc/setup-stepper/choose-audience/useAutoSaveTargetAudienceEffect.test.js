/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAutoSaveTargetAudienceEffect from './useAutoSaveTargetAudienceEffect';

const mockSaveTargetAudience = jest.fn().mockName( 'saveTargetAudience' );
const mockAutoSaveCallback = jest.fn().mockName( 'autoSaveCallbak' );
const mockCreateNotice = jest.fn().mockName( 'createNotice' );

jest.mock( '.~/data', () => ( {
	useAppDispatch: () => ( {
		saveTargetAudience: mockSaveTargetAudience,
	} ),
} ) );

jest.mock( '.~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: mockCreateNotice,
} ) );

describe( 'useAutoSaveTargetAudienceEffect', () => {
	const values = {
		countries: [ 'ES', 'IT', 'FR' ],
		language: 'English',
		locale: 'en_US',
		location: 'selected',
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Autosaving without errors', async () => {
		renderHook( () =>
			useAutoSaveTargetAudienceEffect( values, mockAutoSaveCallback )
		);

		await waitFor( () => {
			expect( mockSaveTargetAudience ).toHaveBeenCalledTimes( 1 );
			expect( mockSaveTargetAudience ).toHaveBeenCalledWith( values );

			expect( mockAutoSaveCallback ).toHaveBeenCalledTimes( 1 );
			expect( mockAutoSaveCallback ).toHaveBeenCalledWith( true );

			//No errors should be displayed
			expect( mockCreateNotice ).toHaveBeenCalledTimes( 0 );
		} );
	} );

	test( 'Autosaving with errors', async () => {
		mockSaveTargetAudience.mockImplementation( () => {
			throw new Error( 'New error!' );
		} );

		renderHook( () =>
			useAutoSaveTargetAudienceEffect( values, mockAutoSaveCallback )
		);

		await waitFor( () => {
			expect( mockSaveTargetAudience ).toHaveBeenCalledTimes( 1 );
			expect( mockSaveTargetAudience ).toHaveBeenCalledWith( values );

			expect( mockAutoSaveCallback ).toHaveBeenCalledTimes( 1 );
			expect( mockAutoSaveCallback ).toHaveBeenCalledWith( false );

			//Errors should be displayed
			expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'There was an error saving target audience data.'
			);
		} );
	} );
} );
