/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CampaignAssetsForm from './campaign-assets-form';

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest
		.fn()
		.mockName( "getSetting( 'currency' )" )
		.mockReturnValue( {
			code: 'EUR',
			symbol: '€',
			precision: 2,
			decimalSeparator: '.',
			thousandSeparator: ',',
			priceFormat: '%1$s %2$s',
		} ),
} ) );

jest.mock( '.~/hooks/useFetchBudgetRecommendation', () => ( {
	__esModule: true,
	default: jest.fn().mockImplementation( () => {
		return [ jest.fn(), null ];
	} ),
} ) );

const alwaysValid = () => ( {} );

describe( 'CampaignAssetsForm', () => {
	it( 'Should extend adapter to meet the required states or functions of assets form', () => {
		const children = jest.fn();

		render(
			<CampaignAssetsForm validate={ alwaysValid }>
				{ children }
			</CampaignAssetsForm>
		);

		const formContextSchema = expect.objectContaining( {
			adapter: expect.objectContaining( {
				assetGroupErrors: expect.any( Object ),
				baseAssetGroup: expect.any( Object ),
				hasImportedAssets: false,
				isEmptyAssetEntityGroup: true,
				isValidAssetGroup: expect.any( Boolean ),
				resetAssetGroup: expect.any( Function ),
				showValidation: expect.any( Function ),
				validationRequestCount: 0,
			} ),
		} );

		expect( children ).toHaveBeenLastCalledWith( formContextSchema );
	} );

	it( 'Should be able to accumulate and reset the validation request count', async () => {
		const inspect = jest.fn();

		render(
			<CampaignAssetsForm validate={ alwaysValid }>
				{ ( { adapter } ) => {
					inspect( adapter.validationRequestCount );

					return (
						<>
							<button onClick={ adapter.showValidation }>
								request
							</button>

							<button onClick={ adapter.resetAssetGroup }>
								reset
							</button>
						</>
					);
				} }
			</CampaignAssetsForm>
		);

		const requestButton = screen.getByRole( 'button', { name: 'request' } );
		const resetButton = screen.getByRole( 'button', { name: 'reset' } );

		expect( inspect ).toHaveBeenLastCalledWith( 0 );

		await userEvent.click( requestButton );

		expect( inspect ).toHaveBeenLastCalledWith( 1 );

		await userEvent.click( requestButton );

		expect( inspect ).toHaveBeenLastCalledWith( 2 );

		await userEvent.click( resetButton );

		expect( inspect ).toHaveBeenLastCalledWith( 0 );
	} );
} );
