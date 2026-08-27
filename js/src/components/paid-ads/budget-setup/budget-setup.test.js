/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, act, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import BudgetSetup from './budget-setup';
import CampaignAssetsForm from '../campaign-assets-form';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import useBudgetMetrics from '~/hooks/useBudgetMetrics';

jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest
		.fn()
		.mockReturnValue( { googleAdsAccount: { code: 'USD', symbol: '$' } } )
);

jest.mock( '~/hooks/useBudgetRecommendation', () =>
	jest.fn().mockName( 'useBudgetRecommendation' )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockName( 'useRaiseBudgetRecommendations' )
);

jest.mock( '~/hooks/useBudgetMetrics', () =>
	jest.fn().mockImplementation( ( countryCodes, dailyBudget ) => {
		return {
			hasResolved: true,
			data: {
				currency: 'USD',
				country: 'US',
				dailyBudget,
				metrics: {
					// Multiply by 7 because custom budget would show the same metrics as the recommended ones.
					cost: dailyBudget * 7,
					conversions: 2.2,
					conversionsValue: 89.99,
				},
			},
		};
	} )
);

function mockEmptyRaiseBudgetRecommendationsData() {
	useRaiseBudgetRecommendations.mockReturnValue( {
		campaigns: [],
		hasFinishedResolution: true,
	} );
}

function mockRaiseBudgetRecommendationsData() {
	const data = {
		dailyBudgetBaseline: 12,
		recommendedDailyBudget: 15,
		high: {
			currency: 'USD',
			country: 'US',
			dailyBudget: 20.555,
			metrics: {
				cost: 143.885,
				conversions: 2.5,
				conversionsValue: 147.891,
				uplift: 50,
			},
		},
		recommended: {
			currency: 'USD',
			country: 'US',
			dailyBudget: 15,
			metrics: {
				cost: 105,
				conversions: 2.2,
				conversionsValue: 80.9892,
				uplift: -10,
			},
		},
		low: {
			currency: 'USD',
			country: 'US',
			dailyBudget: 7.2449,
			metrics: {
				cost: 50.7143,
				conversions: 2,
				conversionsValue: 80,
				uplift: 0.33,
			},
		},
	};

	useRaiseBudgetRecommendations.mockReturnValue( {
		campaigns: [ data ],
		hasFinishedResolution: true,
	} );
}

function mockBudgetRecommendation( ...availableKeys ) {
	const data = {
		dailyBudgetBaseline: 12,
		recommendedDailyBudget: 15,
		high: {
			currency: 'USD',
			country: 'US',
			dailyBudget: 20.555,
			metrics: {
				cost: 143.885,
				conversions: 2.5,
				conversionsValue: 98.594,
			},
		},
		recommended: {
			currency: 'USD',
			country: 'US',
			dailyBudget: 15,
			metrics: {
				cost: 105,
				conversions: 2.2,
				conversionsValue: 89.988,
			},
		},
		low: {
			currency: 'USD',
			country: 'US',
			dailyBudget: 7.2449,
			metrics: {
				cost: 50.7143,
				conversions: 2,
				conversionsValue: 80,
			},
		},
	};

	if ( availableKeys.length !== 0 ) {
		Object.keys( data ).forEach( ( key ) => {
			if ( ! availableKeys.includes( key ) ) {
				delete data[ key ];
			}
		} );
	}

	useBudgetRecommendation.mockReturnValue( {
		hasResolved: true,
		data,
	} );

	mockEmptyRaiseBudgetRecommendationsData();
}

describe( 'BudgetSetup', () => {
	const countries = [ 'US' ];
	let Wrapper;

	function getOption( name ) {
		return screen.getByRole( 'radio', { name } );
	}

	function queryOption( name ) {
		return screen.queryByRole( 'radio', { name } );
	}

	beforeEach( () => {
		mockBudgetRecommendation();

		Wrapper = ( {
			campaignID,
			initLevel,
			initAmount,
			hideRecommendations,
		} ) => {
			const initialCampaign = {};
			if ( initLevel ) {
				initialCampaign.level = initLevel;
			}

			if ( Number.isFinite( initAmount ) ) {
				initialCampaign.amount = initAmount;
				initialCampaign.currentAmount = initAmount;
			}

			if ( initLevel === 'current' && Number.isFinite( initAmount ) ) {
				initialCampaign.currentAmount = initAmount;
			}

			if ( campaignID ) {
				initialCampaign.id = campaignID;
			}

			return (
				<CampaignAssetsForm
					countryCodes={ countries }
					initialCampaign={ initialCampaign }
				>
					<BudgetSetup hideRecommendations={ hideRecommendations } />
				</CampaignAssetsForm>
			);
		};
	} );

	it( 'should render metrics headers', () => {
		render( <Wrapper /> );

		expect( screen.getByText( 'Weekly conversions' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Weekly conv. value' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Weekly cost' ) ).toBeInTheDocument();
	} );

	it( 'should render high, recommended and low options and their metrics', () => {
		render( <Wrapper /> );

		expect( getOption( 'high' ) ).toBeInTheDocument();
		expect( screen.getByText( 'High' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( '$20.56/day' ) ).toBeInTheDocument();
		expect( screen.getByText( '2.5' ) ).toBeInTheDocument();
		expect( screen.getByText( '$98.59' ) ).toBeInTheDocument();
		expect( screen.getByText( '$143.89' ) ).toBeInTheDocument();

		expect( getOption( 'recommended' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Recommended' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( '$15.00/day' ) ).toBeInTheDocument();
		expect( screen.getByText( '2.2' ) ).toBeInTheDocument();
		expect( screen.getByText( '$89.99' ) ).toBeInTheDocument();
		expect( screen.getByText( '$105.00' ) ).toBeInTheDocument();

		expect( getOption( 'low' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Low' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( '$7.24/day' ) ).toBeInTheDocument();
		expect( screen.getByText( '2' ) ).toBeInTheDocument();
		expect( screen.getByText( '$80.00' ) ).toBeInTheDocument();
		expect( screen.getByText( '$50.71' ) ).toBeInTheDocument();
	} );

	it( 'should render available recommendation options', () => {
		mockBudgetRecommendation( 'high', 'recommended' );
		const { rerender } = render( <Wrapper /> );

		expect( getOption( 'high' ) ).toBeInTheDocument();
		expect( getOption( 'recommended' ) ).toBeInTheDocument();
		expect( queryOption( 'low' ) ).not.toBeInTheDocument();

		mockBudgetRecommendation( 'recommended' );
		rerender( <Wrapper /> );

		expect( getOption( 'recommended' ) ).toBeInTheDocument();
		expect( queryOption( 'high' ) ).not.toBeInTheDocument();
		expect( queryOption( 'low' ) ).not.toBeInTheDocument();

		mockBudgetRecommendation( '' );
		rerender( <Wrapper /> );

		expect( queryOption( 'high' ) ).not.toBeInTheDocument();
		expect( queryOption( 'recommended' ) ).not.toBeInTheDocument();
		expect( queryOption( 'low' ) ).not.toBeInTheDocument();
	} );

	it( 'should hide recommendations when `hideRecommendations` is true', () => {
		const { rerender } = render( <Wrapper /> );

		expect( getOption( 'high' ) ).toBeInTheDocument();
		expect( getOption( 'recommended' ) ).toBeInTheDocument();
		expect( getOption( 'low' ) ).toBeInTheDocument();
		expect( getOption( 'custom' ) ).toBeInTheDocument();

		rerender( <Wrapper hideRecommendations /> );

		expect( queryOption( 'high' ) ).not.toBeInTheDocument();
		expect( queryOption( 'recommended' ) ).not.toBeInTheDocument();
		expect( queryOption( 'low' ) ).not.toBeInTheDocument();
		expect( getOption( 'custom' ) ).toBeInTheDocument();
	} );

	it( 'should toggle the input and metrics when selecting the custom option', async () => {
		const user = userEvent.setup();
		render( <Wrapper /> );

		const customOption = getOption( 'custom' );
		expect( customOption ).not.toBeChecked();

		await user.click( customOption );

		expect( customOption ).toBeChecked();
		expect( screen.getByRole( 'textbox' ) ).toBeInTheDocument();
		expect( screen.getAllByText( '2.2' ) ).toHaveLength( 2 );
		expect( screen.getAllByText( '$89.99' ) ).toHaveLength( 2 );
		expect( screen.getAllByText( '$105.00' ) ).toHaveLength( 2 );
	} );

	it( 'should reflect the initial level and amount given by the form context to set the pre-selected option and custom budget value', async () => {
		const user = userEvent.setup();
		const { rerender } = render( <Wrapper key="1" /> );

		expect( getOption( 'recommended' ) ).toBeChecked();
		expect( getOption( 'high' ) ).not.toBeChecked();
		expect( getOption( 'low' ) ).not.toBeChecked();
		expect( getOption( 'custom' ) ).not.toBeChecked();
		await user.click( getOption( 'custom' ) );
		expect( screen.getByRole( 'textbox' ) ).toHaveValue( '15.00' );

		rerender( <Wrapper initLevel="high" key="2" /> );

		expect( getOption( 'recommended' ) ).not.toBeChecked();
		expect( getOption( 'high' ) ).toBeChecked();
		expect( getOption( 'low' ) ).not.toBeChecked();
		expect( getOption( 'custom' ) ).not.toBeChecked();

		rerender( <Wrapper initAmount={ 99.99 } initLevel="custom" key="3" /> );
		expect( getOption( 'recommended' ) ).not.toBeChecked();
		expect( getOption( 'high' ) ).not.toBeChecked();
		expect( getOption( 'low' ) ).not.toBeChecked();
		expect( getOption( 'custom' ) ).toBeChecked();
		expect( screen.getByRole( 'textbox' ) ).toHaveValue( '99.99' );
	} );

	it( 'should debounce updates to the `budget` value called to `useBudgetMetrics` when editing the value of the custom budget input.', async () => {
		jest.useFakeTimers();

		const user = userEvent.setup( {
			advanceTimers: jest.advanceTimersByTime,
		} );
		render( <Wrapper /> );

		await user.click( getOption( 'custom' ) );

		expect( useBudgetMetrics ).toHaveBeenLastCalledWith( countries, 15 );

		const input = screen.getByRole( 'textbox' );
		await user.clear( input );
		await user.type( input, '12.34', { delay: 0.2 } );

		expect( input ).toHaveValue( '12.34' );
		expect( useBudgetMetrics ).toHaveBeenLastCalledWith( countries, 15 );

		await act( async () => jest.advanceTimersByTime( 1000 ) );

		expect( useBudgetMetrics ).toHaveBeenLastCalledWith( countries, 12.34 );

		jest.useRealTimers();
		jest.clearAllTimers();
	} );

	it( 'should show help message when the entered custom budget is less than 30% of the daily budget baseline', async () => {
		const user = userEvent.setup();
		const message = 'Please make sure daily average cost is at least $4.00';

		render( <Wrapper /> );

		await user.click( getOption( 'custom' ) );

		expect( screen.queryByText( message ) ).not.toBeInTheDocument();

		const input = screen.getByRole( 'textbox' );
		await user.clear( input );
		await user.type( input, '3.99' );
		await user.tab();

		expect( screen.getByText( message ) ).toBeInTheDocument();

		await user.clear( input );
		await user.type( input, '4' );
		await user.tab();

		expect( screen.queryByText( message ) ).not.toBeInTheDocument();
	} );

	it( 'should notice the recommended budget when the custom budget is lower than the lowest recommended one and not less than 30% of the daily budget baseline', async () => {
		const user = userEvent.setup();
		const notice = `Your budget is lower than other advertisers' budgets, which may affect performance. For best results, we recommend at least $15.00 per day.`;

		const { container } = render( <Wrapper /> );

		await user.click( getOption( 'custom' ) );

		expect( container ).not.toHaveTextContent( notice );

		const input = screen.getByRole( 'textbox' );
		await user.clear( input );
		await user.type( input, '7.23' );

		expect( container ).toHaveTextContent( notice );

		await user.keyboard( '{End}{Backspace}4' );

		expect( container ).not.toHaveTextContent( notice );
	} );

	it( 'should not show the recommended budget notice when the `hideRecommendations` prop is true', async () => {
		const notice = `Your budget is lower than other advertisers' budgets, which may affect performance. For best results, we recommend at least $15.00 per day.`;

		const { rerender, container } = render(
			<Wrapper initAmount={ 7 } initLevel="custom" />
		);

		expect( container ).toHaveTextContent( notice );

		rerender( <Wrapper hideRecommendations /> );

		expect( container ).not.toHaveTextContent( notice );

		rerender( <Wrapper /> );

		expect( container ).toHaveTextContent( notice );
	} );

	it( 'should set custom budget input to the same value as the Recommended row when clicking "Set custom budget"', async () => {
		const user = userEvent.setup();
		render( <Wrapper initAmount={ 15 } initLevel="current" /> );

		expect( getOption( 'current' ) ).toBeChecked();

		await user.click( getOption( 'custom' ) );

		expect( getOption( 'custom' ) ).toBeChecked();

		const input = screen.getByRole( 'textbox' );
		expect( input ).toHaveValue( '15.00' );
	} );

	it( 'should not render "current" row when no current amount is given', () => {
		render( <Wrapper /> );

		expect( queryOption( 'current' ) ).not.toBeInTheDocument();
	} );

	describe( 'Edit campaign', () => {
		beforeEach( () => {
			mockRaiseBudgetRecommendationsData();
		} );

		it( 'should display the uplift badge for each recommendation option based on the raise data', () => {
			const { container } = render( <Wrapper campaignID={ 1234 } /> );

			const positiveBadgeElement = container.querySelector(
				'.gla-delta-value--positive'
			);
			expect( positiveBadgeElement ).toBeInTheDocument();
			expect(
				within( positiveBadgeElement ).getByText( '+50%' )
			).toBeInTheDocument();

			const negativeBadgeElement = container.querySelector(
				'.gla-delta-value--negative'
			);
			expect( negativeBadgeElement ).toBeInTheDocument();
			expect(
				within( negativeBadgeElement ).getByText( '-10%' )
			).toBeInTheDocument();
		} );

		it( 'should display raise budget recommendation data if available', () => {
			render( <Wrapper campaignID={ 1234 } /> );

			expect( getOption( 'high' ) ).toBeInTheDocument();
			expect( screen.getByText( 'High' ) ).toBeInTheDocument();
			expect( screen.getByLabelText( '$20.56/day' ) ).toBeInTheDocument();
			expect( screen.getByText( '2.5' ) ).toBeInTheDocument();
			expect( screen.getByText( '$147.89' ) ).toBeInTheDocument();
			expect( screen.getByText( '$143.89' ) ).toBeInTheDocument();

			expect( getOption( 'recommended' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Recommended' ) ).toBeInTheDocument();
			expect( screen.getByLabelText( '$15.00/day' ) ).toBeInTheDocument();
			expect( screen.getByText( '2.2' ) ).toBeInTheDocument();
			expect( screen.getByText( '$80.99' ) ).toBeInTheDocument();
			expect( screen.getByText( '$105.00' ) ).toBeInTheDocument();

			expect( getOption( 'low' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Low' ) ).toBeInTheDocument();
			expect( screen.getByLabelText( '$7.24/day' ) ).toBeInTheDocument();
			expect( screen.getByText( '2' ) ).toBeInTheDocument();
			expect( screen.getByText( '$80.00' ) ).toBeInTheDocument();
			expect( screen.getByText( '$50.71' ) ).toBeInTheDocument();
		} );

		it( 'should fallback to the regular budget recommendation data if no raise data is available', () => {
			const { rerender } = render( <Wrapper campaignID={ 1234 } /> );

			expect( getOption( 'high' ) ).toBeInTheDocument();
			expect( screen.getByLabelText( '$20.56/day' ) ).toBeInTheDocument();
			expect( screen.getByText( '$147.89' ) ).toBeInTheDocument();

			mockEmptyRaiseBudgetRecommendationsData();
			rerender( <Wrapper campaignID={ 1234 } /> );

			expect( getOption( 'high' ) ).toBeInTheDocument();
			expect( screen.getByLabelText( '$20.56/day' ) ).toBeInTheDocument();
			expect( screen.getByText( '$143.89' ) ).toBeInTheDocument();

			expect( getOption( 'recommended' ) ).toBeInTheDocument();
			expect( screen.getByLabelText( '$15.00/day' ) ).toBeInTheDocument();
			expect( screen.getByText( '$105.00' ) ).toBeInTheDocument();

			expect( getOption( 'low' ) ).toBeInTheDocument();
			expect( screen.getByLabelText( '$7.24/day' ) ).toBeInTheDocument();
			expect( screen.getByText( '$50.71' ) ).toBeInTheDocument();
		} );
	} );
} );
