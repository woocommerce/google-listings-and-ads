/**
 * Internal dependencies
 */
import { createProgramsFilterConfig } from './filter-config';

function getAutocompleterOptions( config ) {
	const filter = config.filters.find( ( el ) => el?.settings?.autocompleter );
	return filter.settings.autocompleter.options;
}

describe( 'createProgramsFilterConfig', () => {
	let programA, programB, dataA, dataB, dataLoading;
	beforeEach( () => {
		programA = { id: 'A' };
		programB = { id: 'B' };
		dataA = { loaded: true, data: [ programA ] };
		dataB = { loaded: true, data: [ programB ] };
		dataLoading = { loaded: false, data: [] };
	} );

	test( 'should wait for data loaded to resolve programs asynchronously', async () => {
		const getConfig = createProgramsFilterConfig();

		const config = getConfig( dataLoading );
		const initialOptions = getAutocompleterOptions( config );

		// Not easily testable with Jest:
		// expect( initialOptions ).to.be.not.fulfilled;

		// Use the initial options promise, and assert it will eventually be resolved with programs that wil come.
		const initialOptionsAssertion = initialOptions().then( ( programs ) => {
			// The promise from the initial config should resolve with first loaded data.
			expect( programs ).toContain( programA );
		} );

		// Feed the config with data.
		const configWithA = getConfig( dataA );

		// Assert options will resolve with programs.
		const optionsWithA = getAutocompleterOptions( configWithA );
		expect( await optionsWithA() ).toContain( programA );

		// Make sure the initial promise is also resolved and asserted.
		// Eslint complains about await for some reason.
		return initialOptionsAssertion;
	} );

	test( 'should keep updating and resolving programs if no change in `loaded` status between continuous updates', async () => {
		const getConfig = createProgramsFilterConfig();

		getConfig( dataA );
		// Update with the same data.
		const configA = getConfig( dataA );
		const options = getAutocompleterOptions( configA );
		expect( await options() ).toContain( programA );

		// Update with a new data.
		const configB = getConfig( dataB );
		const optionsAfterB = getAutocompleterOptions( configB );

		// Assert the options will eventually resolve with new programs.
		const programsAfterB = await optionsAfterB();
		expect( programsAfterB ).not.toContain( programA );
		expect( programsAfterB ).toContain( programB );
	} );

	test( 'should resolve next programs if the `loaded` status is changed', async () => {
		const getConfig = createProgramsFilterConfig();

		const config = getConfig( dataA );
		const options = getAutocompleterOptions( config );
		expect( await options() ).toContain( programA );

		// Start loading new data.
		const configReloading = getConfig( dataLoading );
		const optionsReloading = getAutocompleterOptions( configReloading );
		// Not easily testable with Jest:
		// expect( initialOptions ).to.be.not.fulfilled;

		// We want assert it will eventually resolve with new data, without waiting for it here.
		const loadingOptionsAssertion = optionsReloading().then(
			( programs ) => {
				// The promise from the reloading config, should resolve with the loaded data.
				expect( programs ).toContain( programB );
			}
		);

		// Update with a new data.
		const configWithB = getConfig( dataB );
		const optionsAfterB = getAutocompleterOptions( configWithB );
		const programsAfterB = await optionsAfterB();
		expect( programsAfterB ).not.toContain( programA );
		expect( programsAfterB ).toContain( programB );

		// Make sure the initial promise is also resolved and asserted.
		// Eslint complains about await for some reason.
		return loadingOptionsAssertion;
	} );
} );
