/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import OptInAssetSuggestions from './opt-in-asset-suggestions';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAssetSuggestionsOnOptIn from '~/hooks/useAssetSuggestionsOnOptIn';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );
jest.mock( '~/hooks/useAssetSuggestionsOnOptIn' );
jest.mock( './gen-ai-progress/gen-ai-progress-card', () => () => (
	<div>Google AI suggestions</div>
) );

describe( 'OptInAssetSuggestions', () => {
	function mockFetching( isFetchingAssets ) {
		useAdaptiveFormContext.mockReturnValue( {
			adapter: { isFetchingAssets },
		} );
	}

	it( 'starts the suggestions on opt-in', () => {
		mockFetching( false );
		render( <OptInAssetSuggestions /> );

		expect( useAssetSuggestionsOnOptIn ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'shows the progress card while suggestions are being fetched', () => {
		mockFetching( true );
		render( <OptInAssetSuggestions /> );

		expect(
			screen.getByText( 'Google AI suggestions' )
		).toBeInTheDocument();
	} );

	it( 'closes the progress card once suggestions are fetched or skipped', () => {
		mockFetching( true );
		const { rerender } = render( <OptInAssetSuggestions /> );

		mockFetching( false );
		rerender( <OptInAssetSuggestions /> );

		expect(
			screen.queryByText( 'Google AI suggestions' )
		).not.toBeInTheDocument();
	} );
} );
