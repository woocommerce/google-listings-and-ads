/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useEventPropertiesFilter from './useEventPropertiesFilter';
import { filterPropertiesMap } from '.~/utils/tracks';

describe( 'useEventPropertiesFilter', () => {
	const SETUP = 'test_filter_setup';
	const FORM = 'test_filter_form';

	beforeAll( () => {
		filterPropertiesMap.set( SETUP, [ 'context', 'step' ] );
		filterPropertiesMap.set( FORM, [ 'context', 'valid' ] );
	} );

	afterAll( () => {
		filterPropertiesMap.delete( SETUP );
		filterPropertiesMap.delete( FORM );
	} );

	it( 'should merge the extra properties over the incoming ones', () => {
		renderHook( () => useEventPropertiesFilter( SETUP, { step: 1 } ) );
		const { result: setup } = renderHook( () =>
			useEventPropertiesFilter( SETUP )
		);

		expect( setup.current() ).toEqual( { step: 1 } );
		expect(
			setup.current( { action: 'confirm', context: 'ads', step: 2 } )
		).toEqual( { action: 'confirm', context: 'ads', step: 1 } );
	} );

	it( 'should only handle the valid names for extra properties', () => {
		const { result: setup } = renderHook( () =>
			useEventPropertiesFilter( SETUP, { step: 1, aciton: 'confirm' } )
		);

		expect( setup.current() ).toEqual( { step: 1 } );
	} );

	it( 'should add and apply extra properties across hook instances between the same filter name', () => {
		const { result: form } = renderHook( () =>
			useEventPropertiesFilter( FORM, { context: 'tax', valid: true } )
		);

		const { result: setupContext } = renderHook( () =>
			useEventPropertiesFilter( SETUP, { context: 'ads' } )
		);
		const { result: setupStep } = renderHook( () =>
			useEventPropertiesFilter( SETUP, { step: 2 } )
		);
		const { result: setup } = renderHook( () =>
			useEventPropertiesFilter( SETUP )
		);

		expect( form.current() ).toEqual( { context: 'tax', valid: true } );
		expect( setupContext.current() ).toEqual( { context: 'ads', step: 2 } );
		expect( setupStep.current() ).toEqual( { context: 'ads', step: 2 } );
		expect( setup.current() ).toEqual( { context: 'ads', step: 2 } );
	} );

	it( 'should reflect the changes in filter name and extra properties', () => {
		const { result: setup } = renderHook( () =>
			useEventPropertiesFilter( SETUP )
		);
		const { result: form } = renderHook( () =>
			useEventPropertiesFilter( FORM )
		);
		const { rerender } = renderHook(
			( { filterName, extraProperties } ) =>
				useEventPropertiesFilter( filterName, extraProperties ),
			{
				initialProps: {
					filterName: SETUP,
					extraProperties: { context: 'onboarding' },
				},
			}
		);

		expect( setup.current() ).toEqual( { context: 'onboarding' } );
		expect( form.current() ).toBeUndefined();

		rerender( {
			filterName: FORM,
			extraProperties: { context: 'tax' },
		} );

		expect( setup.current() ).toBeUndefined();
		expect( form.current() ).toEqual( { context: 'tax' } );
	} );
} );
