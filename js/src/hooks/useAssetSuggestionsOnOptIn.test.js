/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAssetSuggestionsOnOptIn from './useAssetSuggestionsOnOptIn';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { ADVERTISING_SOURCE, ASSET_FORM_KEY } from '~/constants';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );

describe( 'useAssetSuggestionsOnOptIn', () => {
	let fetchAssets;
	let resetAssetGroup;
	let formState;

	// Builds a fresh adapter on every call, like `CampaignAssetsForm` does on every render.
	function mockFormContext() {
		useAdaptiveFormContext.mockImplementation( () => ( {
			values: {
				advertisingSource: formState.advertisingSource,
				includeCreativeAssets: formState.includeCreativeAssets,
			},
			adapter: {
				baseAssetGroup: {
					[ ASSET_FORM_KEY.FINAL_URL ]: formState.finalUrl,
				},
				isFetchingAssets: formState.isFetchingAssets,
				fetchAssets: ( ...args ) => fetchAssets( ...args ),
				resetAssetGroup: ( ...args ) => resetAssetGroup( ...args ),
			},
		} ) );
	}

	function renderUseAssetSuggestionsOnOptIn() {
		mockFormContext();
		const { rerender } = renderHook( () => useAssetSuggestionsOnOptIn() );

		return ( changes ) => {
			Object.assign( formState, changes );
			rerender();
		};
	}

	beforeEach( () => {
		fetchAssets = jest.fn().mockResolvedValue( { final_url: 'https://a' } );
		resetAssetGroup = jest.fn();
		formState = {
			advertisingSource: ADVERTISING_SOURCE.PRODUCT,
			includeCreativeAssets: false,
			finalUrl: null,
			isFetchingAssets: false,
		};
	} );

	it( 'requests the homepage suggestions once when the box is ticked on a products campaign, then resets the asset group with the result', async () => {
		const update = renderUseAssetSuggestionsOnOptIn();
		expect( fetchAssets ).not.toHaveBeenCalled();

		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );

		expect( fetchAssets ).toHaveBeenCalledTimes( 1 );
		expect( fetchAssets ).toHaveBeenCalledWith( 0, 'homepage' );
		expect( resetAssetGroup ).toHaveBeenCalledTimes( 1 );
		expect( resetAssetGroup ).toHaveBeenCalledWith( {
			final_url: 'https://a',
		} );
	} );

	it( 'does not request suggestions when the box is ticked on a page campaign', async () => {
		formState.advertisingSource = ADVERTISING_SOURCE.PAGE;
		const update = renderUseAssetSuggestionsOnOptIn();

		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );

		expect( fetchAssets ).not.toHaveBeenCalled();
		expect( resetAssetGroup ).not.toHaveBeenCalled();
	} );

	it( 'does not request suggestions again after an untick and a tick once a Final URL is set', async () => {
		const update = renderUseAssetSuggestionsOnOptIn();

		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );
		expect( fetchAssets ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			update( {
				includeCreativeAssets: false,
				finalUrl: 'https://a',
			} );
		} );
		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );

		expect( fetchAssets ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not request suggestions a second time while the first request is still running', async () => {
		let resolveFetch;
		fetchAssets.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveFetch = resolve;
			} )
		);
		const update = renderUseAssetSuggestionsOnOptIn();

		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );
		await act( async () => {
			update( { includeCreativeAssets: false } );
		} );
		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );

		expect( fetchAssets ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			resolveFetch( {} );
		} );

		expect( resetAssetGroup ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not request suggestions when the form is already fetching assets', async () => {
		formState.isFetchingAssets = true;
		const update = renderUseAssetSuggestionsOnOptIn();

		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );

		expect( fetchAssets ).not.toHaveBeenCalled();
	} );

	it( 'resets the asset group with the suggestions the page returns when Google AI is skipped', async () => {
		const pageSuggestions = {
			final_url: 'https://a',
			headline: [ 'From the page' ],
		};
		fetchAssets.mockResolvedValue( pageSuggestions );
		const update = renderUseAssetSuggestionsOnOptIn();

		await act( async () => {
			update( { includeCreativeAssets: true } );
		} );

		expect( resetAssetGroup ).toHaveBeenCalledWith( pageSuggestions );
	} );
} );
