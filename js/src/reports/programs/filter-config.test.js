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
		programA = { id: 65 };
		programB = { id: 66 };
		dataA = { loaded: true, data: [ programA ] };
		dataB = { loaded: true, data: [ programB ] };
		dataLoading = { loaded: false, data: [] };
	} );

	describe( 'options', () => {
		test( 'should wait for data loaded to resolve programs asynchronously', async () => {
			const getConfig = createProgramsFilterConfig();

			const config = getConfig( dataLoading );
			const initialOptions = getAutocompleterOptions( config );

			// Not easily testable with Jest:
			// expect( initialOptions ).to.be.not.fulfilled;

			// Use the initial options promise, and assert it will eventually be resolved with programs that wil come.
			const initialOptionsAssertion = initialOptions().then(
				( programs ) => {
					// The promise from the initial config should resolve with first loaded data.
					expect( programs ).toContain( programA );
				}
			);

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

	describe( 'getLabels', () => {
		function getGetLabels( config ) {
			const filter = config.filters.find(
				( el ) => el?.settings?.getLabels
			);
			return filter.settings.getLabels;
		}
		let labelA, labelB;
		beforeEach( () => {
			labelA = { key: programA.id, label: programA.name };
			labelB = { key: programB.id, label: programB.name };
		} );
		test( 'after initial run should provide getLabels that would eventually resolve with data', () => {
			const getConfig = createProgramsFilterConfig();

			const config = getConfig( dataLoading );
			const initialGetLabels = getGetLabels( config );

			// Not easily testable with Jest:
			// expect( initialOptions ).to.be.not.fulfilled;

			// Use the initial options promise, and assert it will eventually be resolved with programs that wil come.
			const initialOptionsAssertion = initialGetLabels(
				'' + programA.id
			).then( ( labels ) => {
				expect( labels ).toContainEqual( labelA );
			} );

			// Feed the config with data.
			getConfig( dataA );

			// Make sure the initial promise is resolved and asserted.
			// Eslint complains about await for some reason.
			return initialOptionsAssertion;
		} );

		test( 'after data is loaded getLabels should resolve with it', async () => {
			const getConfig = createProgramsFilterConfig();
			// Initial loading.
			getConfig( dataLoading );
			// Feed the config with data.
			const getLabels = getGetLabels( getConfig( dataA ) );

			expect( await getLabels( '' + programA.id ) ).toContainEqual(
				labelA
			);
		} );

		test( 'after data is updated getLabels should resolve with the new data', async () => {
			const getConfig = createProgramsFilterConfig();
			// Initial loading.
			getConfig( dataLoading );
			// First data.
			getConfig( dataA );
			// Update the config with the new data.
			const getLabels = getGetLabels( getConfig( dataB ) );

			const updatedLabels = await getLabels( '' + programB.id );

			expect( updatedLabels ).not.toContainEqual( labelA );
			expect( updatedLabels ).toContainEqual( labelB );
		} );

		test( 'when the new data is being loaded getLabels should return a promise that eventually resolves with the new data.', async () => {
			const getConfig = createProgramsFilterConfig();
			// Initial loading.
			getConfig( dataLoading );
			// First data.
			getConfig( dataA );
			// Re-loading.
			const getLabels = getGetLabels( getConfig( dataLoading ) );
			// Assert this promise.
			const reloadingLabels = getLabels( '' + programB.id ).then(
				( updatedLabels ) => {
					expect( updatedLabels ).not.toContainEqual( labelA );
					expect( updatedLabels ).toContainEqual( labelB );
				}
			);
			// Update.
			getConfig( dataB );

			// Wait for resolution.
			return reloadingLabels;
		} );
	} );
} );
