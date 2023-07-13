jest.mock( '.~/components/adaptive-form', () => ( {
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

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AssetGroupSection from '.~/components/paid-ads/asset-group/asset-group-section';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';

jest.mock( '.~/components/paid-ads/asset-group/asset-group-card', () =>
	jest.fn( ( props ) => <div { ...props } /> ).mockName( 'AssetGroupCard' )
);

describe( 'AssetGroupSection', () => {
	test( 'Component renders', () => {
		render( <AssetGroupSection /> );
		expect(
			screen.queryByText( /Add additional assets/i )
		).toBeInTheDocument();
	} );

	test( 'Component not showing Tip if there are no imported assets', () => {
		render( <AssetGroupSection /> );
		expect(
			screen.queryByText(
				'We auto-populated assets directly from your Final URL. We encourage you to edit or add more in order to best showcase your business.'
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
		render( <AssetGroupSection /> );
		expect(
			screen.queryByText(
				'We auto-populated assets directly from your Final URL. We encourage you to edit or add more in order to best showcase your business.'
			)
		).toBeInTheDocument();
	} );
} );
