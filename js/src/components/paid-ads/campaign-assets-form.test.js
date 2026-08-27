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
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockName( 'useAdsCurrency' )
);

jest.mock( '~/hooks/useBudgetRecommendation', () =>
	jest.fn().mockName( 'useBudgetRecommendation' )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockReturnValue( {
		hasFinishedResolution: true,
	} )
);

const alwaysValid = () => ( {} );

describe( 'CampaignAssetsForm', () => {
	beforeEach( () => {
		useAdsCurrency.mockReturnValue( {
			formatAmount: jest.fn().mockName( 'formatAmount' ),
		} );

		useBudgetRecommendation.mockReturnValue( {
			hasResolved: true,
			data: {
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 15,
				recommended: { dailyBudget: 15 },
				high: { dailyBudget: 30 },
				low: { dailyBudget: 5 },
			},
		} );
	} );

	it( 'Should extend adapter to meet the required states of ads campaign form', () => {
		const children = jest.fn();
		const countryCodes = [ 'US', 'JP', 'TW' ];

		render(
			<CampaignAssetsForm
				countryCodes={ countryCodes }
				validate={ alwaysValid }
			>
				{ children }
			</CampaignAssetsForm>
		);

		const formContextSchema = expect.objectContaining( {
			adapter: expect.objectContaining( {
				countryCodes,
				budgetRecommendation: {
					dailyBudgetBaseline: 13,
					recommendedDailyBudget: 15,
					recommended: { dailyBudget: 15 },
					high: { dailyBudget: 30 },
					low: { dailyBudget: 5 },
				},
			} ),
		} );

		expect( children ).toHaveBeenLastCalledWith( formContextSchema );
	} );

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
		const user = userEvent.setup();
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

		await user.click( requestButton );

		expect( inspect ).toHaveBeenLastCalledWith( 1 );

		await user.click( requestButton );

		expect( inspect ).toHaveBeenLastCalledWith( 2 );

		await user.click( resetButton );

		expect( inspect ).toHaveBeenLastCalledWith( 0 );
	} );

	it( 'Should resolve the default dailyBudget for the initial form values', () => {
		const children = jest.fn();

		render(
			<CampaignAssetsForm
				initialCampaign={ { amount: 10 } }
				validate={ alwaysValid }
			>
				{ children }
			</CampaignAssetsForm>
		);

		const formContextSchema = expect.objectContaining( {
			values: expect.objectContaining( {
				amount: 10,
				dailyBudget: 15,
				level: 'recommended',
			} ),
		} );

		expect( children ).toHaveBeenLastCalledWith( formContextSchema );
	} );

	it( 'Should resolve the available level for the initial form values', () => {
		useBudgetRecommendation.mockReturnValue( {
			hasResolved: true,
			data: {
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 15,
				recommended: { dailyBudget: 15 },
			},
		} );

		const children = jest.fn();

		render(
			<CampaignAssetsForm
				initialCampaign={ { amount: 10, level: 'high' } }
				validate={ alwaysValid }
			>
				{ children }
			</CampaignAssetsForm>
		);

		const formContextSchema = expect.objectContaining( {
			values: expect.objectContaining( {
				amount: 10,
				dailyBudget: 15,
				level: 'recommended',
			} ),
		} );

		expect( children ).toHaveBeenLastCalledWith( formContextSchema );
	} );

	it( 'Should show the current level as selected one when editing a paid campaign', () => {
		useBudgetRecommendation.mockReturnValue( {
			hasResolved: true,
			data: {
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 15,
			},
		} );

		const children = jest.fn();

		render(
			<CampaignAssetsForm
				initialCampaign={ {
					amount: 10,
					currentAmount: 10,
				} }
				validate={ alwaysValid }
			>
				{ children }
			</CampaignAssetsForm>
		);

		const formContextSchema = expect.objectContaining( {
			values: expect.objectContaining( {
				amount: 10,
				currentAmount: 10,
				dailyBudget: 10,
				level: 'current',
			} ),
		} );

		expect( children ).toHaveBeenLastCalledWith( formContextSchema );
	} );

	it( 'Should resolve the dailyBudget from the custom level for the initial form values', () => {
		const children = jest.fn();

		render(
			<CampaignAssetsForm
				initialCampaign={ { amount: 10, level: 'custom' } }
				validate={ alwaysValid }
			>
				{ children }
			</CampaignAssetsForm>
		);

		const formContextSchema = expect.objectContaining( {
			values: expect.objectContaining( {
				amount: 10,
				dailyBudget: 10,
				level: 'custom',
			} ),
		} );

		expect( children ).toHaveBeenLastCalledWith( formContextSchema );
	} );

	it( 'Should resolve dailyBudget when calling back onChange with form values', async () => {
		const user = userEvent.setup();
		const inspect = jest.fn();

		render(
			<CampaignAssetsForm
				initialCampaign={ { amount: 10 } }
				onChange={ inspect }
				validate={ alwaysValid }
			>
				{ ( { setValue } ) => {
					const handleClick = ( e ) => {
						setValue( 'level', e.target.textContent );
					};
					return (
						<>
							<button onClick={ handleClick }>custom</button>
							<button onClick={ handleClick }>high</button>
							<button onClick={ handleClick }>low</button>
							<button onClick={ handleClick }>recommended</button>
						</>
					);
				} }
			</CampaignAssetsForm>
		);

		expect( inspect ).toHaveBeenCalledTimes( 0 );

		await user.click( screen.getByRole( 'button', { name: 'custom' } ) );
		expect( inspect ).toHaveBeenCalledTimes( 1 );
		expect( inspect ).toHaveBeenLastCalledWith(
			{
				name: 'level',
				value: 'custom',
			},
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 10,
				level: 'custom',
			} ),
			true
		);

		await user.click( screen.getByRole( 'button', { name: 'high' } ) );
		expect( inspect ).toHaveBeenCalledTimes( 2 );
		expect( inspect ).toHaveBeenLastCalledWith(
			{
				name: 'level',
				value: 'high',
			},
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 30,
				level: 'high',
			} ),
			true
		);

		await user.click( screen.getByRole( 'button', { name: 'low' } ) );
		expect( inspect ).toHaveBeenCalledTimes( 3 );
		expect( inspect ).toHaveBeenLastCalledWith(
			{
				name: 'level',
				value: 'low',
			},
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 5,
				level: 'low',
			} ),
			true
		);

		await user.click(
			screen.getByRole( 'button', { name: 'recommended' } )
		);
		expect( inspect ).toHaveBeenCalledTimes( 4 );
		expect( inspect ).toHaveBeenLastCalledWith(
			{
				name: 'level',
				value: 'recommended',
			},
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 15,
				level: 'recommended',
			} ),
			true
		);
	} );

	it( 'Should resolve dailyBudget when calling back onSubmit with form values', async () => {
		const user = userEvent.setup();
		const inspect = jest.fn();

		render(
			<CampaignAssetsForm
				initialCampaign={ { amount: 10 } }
				onSubmit={ inspect }
				validate={ alwaysValid }
			>
				{ ( { setValue, handleSubmit } ) => {
					const handleClick = ( e ) => {
						setValue( 'level', e.target.textContent );
					};
					return (
						<>
							<button onClick={ handleClick }>custom</button>
							<button onClick={ handleClick }>high</button>
							<button onClick={ handleClick }>low</button>
							<button onClick={ handleClick }>recommended</button>
							<button onClick={ handleSubmit }>submit</button>
						</>
					);
				} }
			</CampaignAssetsForm>
		);

		const submitButton = screen.getByRole( 'button', { name: 'submit' } );
		expect( inspect ).toHaveBeenCalledTimes( 0 );

		await user.click( screen.getByRole( 'button', { name: 'custom' } ) );
		await user.click( submitButton );
		expect( inspect ).toHaveBeenCalledTimes( 1 );
		expect( inspect ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 10,
				level: 'custom',
			} ),
			expect.any( Object )
		);

		await user.click( screen.getByRole( 'button', { name: 'high' } ) );
		await user.click( submitButton );
		expect( inspect ).toHaveBeenCalledTimes( 2 );
		expect( inspect ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 30,
				level: 'high',
			} ),
			expect.any( Object )
		);

		await user.click( screen.getByRole( 'button', { name: 'low' } ) );
		await user.click( submitButton );
		expect( inspect ).toHaveBeenCalledTimes( 3 );
		expect( inspect ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 5,
				level: 'low',
			} ),
			expect.any( Object )
		);

		await user.click(
			screen.getByRole( 'button', { name: 'recommended' } )
		);
		await user.click( submitButton );
		expect( inspect ).toHaveBeenCalledTimes( 4 );
		expect( inspect ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				amount: 10,
				dailyBudget: 15,
				level: 'recommended',
			} ),
			expect.any( Object )
		);
	} );
} );
