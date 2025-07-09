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
		expect(
			screen.getByText( /Add additional assets/i )
		).toBeInTheDocument();
	} );

	test( 'Component not showing Tip if there are no imported assets', () => {
		render( <AssetGroupHeader /> );
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
		render( <AssetGroupHeader /> );
		expect(
			screen.getByText(
				'We auto-populated assets directly from your Final URL. We encourage you to edit or add more in order to best showcase your business.'
			)
		).toBeInTheDocument();
	} );
} );
