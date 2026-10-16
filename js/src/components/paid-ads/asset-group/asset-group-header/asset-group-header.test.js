jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest
		.fn()
		.mockName( 'useAdaptiveFormContext' )
		.mockImplementation( () => {
			return {
				adapter: {
					baseAssetGroup: { final_url: 'https://example.com' },
					hasImportedAssets: false,
					isEmptyAssetEntityGroup: false,
					resetAssetGroup: jest.fn(),
				},
			};
		} ),
} ) );

jest.mock(
	'~/components/paid-ads/asset-group/asset-group-header/final-url-card',
	() => () => <div className="gla-final-url-card" />
);

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AssetGroupHeader from './asset-group-header';
import { useAdaptiveFormContext } from '~/components/adaptive-form';

describe( 'AssetGroupHeader', () => {
	test( 'Component renders', () => {
		render( <AssetGroupHeader /> );
		expect( screen.getByText( /Add assets/i ) ).toBeInTheDocument();
	} );

	test( 'Component not showing Tip if there are no imported assets', () => {
		render( <AssetGroupHeader /> );
		expect(
			screen.queryByText(
				"We've used your final URL to auto-populate some assets for you. For the best results, we recommend that you add more assets."
			)
		).not.toBeInTheDocument();
	} );

	test( 'Component showing Tip if there are imported assets', () => {
		useAdaptiveFormContext.mockImplementation( () => {
			return {
				adapter: {
					baseAssetGroup: { final_url: 'https://example.com' },
					hasImportedAssets: true,
					isEmptyAssetEntityGroup: false,
					resetAssetGroup: jest.fn(),
				},
			};
		} );
		render( <AssetGroupHeader /> );
		expect(
			screen.getByText(
				"We've used your final URL to auto-populate some assets for you. For the best results, we recommend that you add more assets."
			)
		).toBeInTheDocument();
	} );
} );
