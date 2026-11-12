/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { setSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import CampaignAssetsForm from './campaign-assets-form';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockName( 'useAdsCurrency' )
);

jest.mock( '~/hooks/useBudgetRecommendation', () =>
	jest.fn().mockName( 'useBudgetRecommendation' )
);

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
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

		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'Should extend adapter to meet the required states of ads campaign form', () => {
		const children = jest.fn();
		const countryCodes = [ 'US', 'JP', 'TW' ];

		render(
			<CampaignAssetsForm
				validate={ alwaysValid }
				countryCodes={ countryCodes }
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
				validate={ alwaysValid }
				initialCampaign={ { amount: 10 } }
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
				validate={ alwaysValid }
				initialCampaign={ { amount: 10, level: 'high' } }
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
				validate={ alwaysValid }
				initialCampaign={ {
					amount: 10,
					currentAmount: 10,
				} }
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
				validate={ alwaysValid }
				initialCampaign={ { amount: 10, level: 'custom' } }
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
				validate={ alwaysValid }
				initialCampaign={ { amount: 10 } }
				onChange={ inspect }
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
				validate={ alwaysValid }
				initialCampaign={ { amount: 10 } }
				onSubmit={ inspect }
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

	describe( 'campaign setup values', () => {
		function renderForm( props ) {
			const children = jest.fn();

			const view = render(
				<CampaignAssetsForm validate={ alwaysValid } { ...props }>
					{ children }
				</CampaignAssetsForm>
			);

			return { children, ...view };
		}

		function expectLastValuesToMatch( children, values ) {
			expect( children ).toHaveBeenLastCalledWith(
				expect.objectContaining( {
					values: expect.objectContaining( values ),
				} )
			);
		}

		function mockCurrentTime( isoDateTime ) {
			jest.useFakeTimers( {
				now: new Date( isoDateTime ),
				doNotFake: [
					'setTimeout',
					'clearTimeout',
					'setInterval',
					'clearInterval',
					'setImmediate',
					'clearImmediate',
					'nextTick',
					'queueMicrotask',
					'requestAnimationFrame',
					'cancelAnimationFrame',
					'requestIdleCallback',
					'cancelIdleCallback',
					'performance',
				],
			} );
		}

		function setSiteTimezoneOffset( offset ) {
			setSettings( {
				l10n: { locale: 'en_US' },
				formats: { datetime: 'Y-m-d H:i:s' },
				timezone: { offset, string: '' },
			} );
		}

		afterEach( () => {
			setSiteTimezoneOffset( 0 );
		} );

		it( 'Should start a new campaign with a generated name and the assets opt-in off', () => {
			mockCurrentTime( '2026-11-05T14:30:00Z' );
			setSiteTimezoneOffset( 0 );

			const { children } = renderForm();

			expectLastValuesToMatch( children, {
				campaignName: 'Campaign 2026-11-05 14:30:00',
				includeCreativeAssets: false,
			} );
		} );

		it( 'Should start the advertising source as product when Merchant Center is connected', () => {
			const { children } = renderForm();

			expectLastValuesToMatch( children, {
				advertisingSource: 'product',
			} );
		} );

		it( 'Should start the advertising source as page when Merchant Center is not connected', () => {
			useGoogleMCAccount.mockReturnValue( {
				hasFinishedResolution: true,
				hasGoogleMCConnection: false,
			} );

			const { children } = renderForm();

			expectLastValuesToMatch( children, { advertisingSource: 'page' } );
		} );

		it( 'Should wait for the Merchant Center account to resolve before rendering the form', () => {
			useGoogleMCAccount.mockReturnValue( {
				hasFinishedResolution: false,
				hasGoogleMCConnection: false,
			} );

			const { children, rerender } = renderForm();

			expect( children ).not.toHaveBeenCalled();

			useGoogleMCAccount.mockReturnValue( {
				hasFinishedResolution: true,
				hasGoogleMCConnection: true,
			} );

			rerender(
				<CampaignAssetsForm validate={ alwaysValid }>
					{ children }
				</CampaignAssetsForm>
			);

			expectLastValuesToMatch( children, {
				advertisingSource: 'product',
			} );
			expect( children ).not.toHaveBeenCalledWith(
				expect.objectContaining( {
					values: expect.objectContaining( {
						advertisingSource: 'page',
					} ),
				} )
			);
		} );

		it( 'Should leave the advertising source unset when the Merchant Center account failed to load', () => {
			useGoogleMCAccount.mockReturnValue( {
				hasFinishedResolution: true,
				isPreconditionReady: true,
				googleMCAccount: null,
				hasGoogleMCConnection: false,
			} );

			const { children } = renderForm();

			expectLastValuesToMatch( children, {
				advertisingSource: undefined,
			} );
		} );

		it( 'Should not wait for the Merchant Center account when a starting source is passed', () => {
			useGoogleMCAccount.mockReturnValue( {
				hasFinishedResolution: false,
				hasGoogleMCConnection: false,
			} );

			const { children } = renderForm( {
				initialCampaign: { advertisingSource: 'page' },
			} );

			expectLastValuesToMatch( children, { advertisingSource: 'page' } );
		} );

		it( 'Should generate the default name in the site timezone', () => {
			mockCurrentTime( '2026-11-05T20:30:00Z' );
			setSiteTimezoneOffset( 9 );

			const { children } = renderForm();

			expectLastValuesToMatch( children, {
				campaignName: 'Campaign 2026-11-06 05:30:00',
			} );
		} );

		it( 'Should not change the default name when the form renders again', () => {
			mockCurrentTime( '2026-11-05T14:30:00Z' );
			setSiteTimezoneOffset( 0 );

			const { children, rerender } = renderForm();

			jest.setSystemTime( new Date( '2026-11-05T14:45:00Z' ) );

			rerender(
				<CampaignAssetsForm validate={ alwaysValid }>
					{ children }
				</CampaignAssetsForm>
			);

			expectLastValuesToMatch( children, {
				campaignName: 'Campaign 2026-11-05 14:30:00',
			} );
		} );

		it( 'Should keep the values passed as the initial campaign', () => {
			const { children } = renderForm( {
				initialCampaign: {
					campaignName: 'My campaign',
					advertisingSource: 'page',
					includeCreativeAssets: true,
				},
			} );

			expectLastValuesToMatch( children, {
				campaignName: 'My campaign',
				advertisingSource: 'page',
				includeCreativeAssets: true,
			} );
		} );

		it( 'Should not reset the values when the asset group is reset', async () => {
			const user = userEvent.setup();
			const initialCampaign = {
				campaignName: 'My campaign',
				advertisingSource: 'page',
				includeCreativeAssets: true,
			};
			const inspect = jest.fn();

			render(
				<CampaignAssetsForm
					validate={ alwaysValid }
					initialCampaign={ initialCampaign }
				>
					{ ( { adapter, values } ) => {
						inspect( values );

						return (
							<button onClick={ adapter.resetAssetGroup }>
								reset
							</button>
						);
					} }
				</CampaignAssetsForm>
			);

			await user.click( screen.getByRole( 'button', { name: 'reset' } ) );

			expect( inspect ).toHaveBeenLastCalledWith(
				expect.objectContaining( initialCampaign )
			);
		} );
	} );
} );
