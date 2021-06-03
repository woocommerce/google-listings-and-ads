/**
 * Internal dependencies
 */
import { createProgramsFilterConfig } from './filter-config';

function getAutocompleterOptions( config ) {
	const filter = config.filters.find( ( el ) => el?.settings?.autocompleter );
	return filter.settings.autocompleter.options;
}

describe( 'createProgramsFilterConfig', () => {
	const programA = { id: 'A' };
	const programB = { id: 'B' };
	const dataA = { loaded: true, data: [ programA ] };
	const dataB = { loaded: true, data: [ programB ] };
	const dataLoading = { loaded: false, data: [] };

	test( 'should wait for data loaded to resolve programs asynchronously', () => {
		const getConfig = createProgramsFilterConfig();

		return Promise.resolve()
			.then( () => {
				const config = getConfig( dataLoading );
				const options = getAutocompleterOptions( config );

				// Simulate data status from being loading to loaded,
				// so it should not return the pending `options()` to block this test case.
				options().then( ( programs ) => {
					// The promise from the initial config should be resolved as well.
					expect( programs.includes( programA ) ).toBe( true );
				} );
			} )
			.then( () => {
				const config = getConfig( dataA );
				const options = getAutocompleterOptions( config );
				return options().then( ( programs ) => {
					expect( programs.includes( programA ) ).toBe( true );
				} );
			} );
	} );

	test( 'should keep updating and resolving programs if no change in `loaded` status between continuous updates', () => {
		const getConfig = createProgramsFilterConfig();

		getConfig( dataA );

		return Promise.resolve()
			.then( () => {
				const config = getConfig( dataA );
				const options = getAutocompleterOptions( config );
				return options().then( ( programs ) => {
					expect( programs.includes( programA ) ).toBe( true );
				} );
			} )
			.then( () => {
				const config = getConfig( dataB );
				const options = getAutocompleterOptions( config );
				return options().then( ( programs ) => {
					expect( programs.includes( programA ) ).toBe( false );
					expect( programs.includes( programB ) ).toBe( true );
				} );
			} );
	} );

	test( 'should resolve next programs if the `loaded` status is changed', () => {
		const getConfig = createProgramsFilterConfig();

		return Promise.resolve()
			.then( () => {
				const config = getConfig( dataA );
				const options = getAutocompleterOptions( config );
				return options().then( ( programs ) => {
					expect( programs.includes( programA ) ).toBe( true );
				} );
			} )
			.then( () => {
				getConfig( dataLoading );
			} )
			.then( () => {
				const config = getConfig( dataB );
				const options = getAutocompleterOptions( config );
				return options().then( ( programs ) => {
					expect( programs.includes( programA ) ).toBe( false );
					expect( programs.includes( programB ) ).toBe( true );
				} );
			} );
	} );
} );
