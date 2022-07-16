/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAutoSaveSettingsEffect from './useAutoSaveSettingsEffect';

const mockSaveSettings = jest.fn().mockName( 'saveSettings' );
const mockCreateNotice = jest.fn().mockName( 'createNotice' );

jest.mock( '.~/data', () => ( {
	useAppDispatch: () => ( {
		saveSettings: mockSaveSettings,
	} ),
} ) );

jest.mock( '.~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: mockCreateNotice,
} ) );

describe( 'useAutoSaveSettingsEffect', () => {
	const initialSettings = {
		shipping_rate: null,
		tax_rate: null,
		shipping_time: null,
	};

	const newSettings = {
		shipping_rate: 'automatic',
		tax_rate: null,
		shipping_time: 'flat',
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Autosaving without errors', async () => {
		const { rerender } = renderHook(
			( settings ) => useAutoSaveSettingsEffect( settings ),
			{ initialProps: initialSettings }
		);

		//Should not be call in the first render
		await waitFor( () => {
			expect( mockSaveSettings ).toHaveBeenCalledTimes( 0 );
			expect( mockCreateNotice ).toHaveBeenCalledTimes( 0 );
		} );

		rerender( newSettings );

		await waitFor( () => {
			expect( mockSaveSettings ).toHaveBeenCalledTimes( 1 );
			expect( mockSaveSettings ).toHaveBeenCalledWith( newSettings );
			//No errors should be displayed
			expect( mockCreateNotice ).toHaveBeenCalledTimes( 0 );
		} );
	} );

	test( 'Autosaving with errors', async () => {
		mockSaveSettings.mockImplementation( () => {
			throw new Error( 'New error!' );
		} );

		const { rerender } = renderHook(
			( settings ) => useAutoSaveSettingsEffect( settings ),
			{ initialProps: initialSettings }
		);

		rerender( newSettings );

		await waitFor( () => {
			expect( mockSaveSettings ).toHaveBeenCalledTimes( 1 );
			expect( mockSaveSettings ).toHaveBeenCalledWith( newSettings );

			//Errors should be displayed
			expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'There was an error trying to save settings. Please try again later.'
			);
		} );
	} );
} );
