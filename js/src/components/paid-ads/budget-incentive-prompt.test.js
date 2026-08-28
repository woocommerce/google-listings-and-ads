/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BudgetIncentivePrompt from './budget-incentive-prompt';
import { useAppDispatch } from '~/data';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );

jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest
		.fn()
		.mockReturnValue( { googleAdsAccount: { code: 'USD', symbol: '$' } } )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockReturnValue( {
		hasFinishedResolution: true,
	} )
);

jest.mock( '~/hooks/useCYOIncentives', () =>
	jest.fn().mockReturnValue( {
		hasFinishedResolution: true,
	} )
);

jest.mock( '~/hooks/useBudgetRecommendation', () =>
	jest.fn().mockReturnValue( {
		hasResolved: true,
		data: {
			dailyBudgetBaseline: 13,
			recommendedDailyBudget: 15,
			recommended: {
				currency: 'USD',
				country: 'US',
				dailyBudget: 15,
				metrics: {
					cost: 105,
					conversions: 2.2,
					conversionsValue: 89.98,
				},
			},
			high: {
				currency: 'USD',
				country: 'US',
				dailyBudget: 20.5,
				metrics: {
					cost: 143.5,
					conversions: 2.5,
					conversionsValue: 98.59,
				},
			},
			low: {
				currency: 'USD',
				country: 'US',
				dailyBudget: 7,
				metrics: {
					cost: 49,
					conversions: 2,
					conversionsValue: 80.48,
				},
			},
		},
	} )
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
					cost: dailyBudget * 7,
					conversions: 2.2,
					conversionsValue: 89.98,
				},
			},
		};
	} )
);

describe( 'BudgetIncentivePrompt', () => {
	let Wrapper;
	let fetchAdsIncentiveCredits;
	let onResolved;

	function getPromptButton() {
		return screen.getByRole( 'button', { name: 'Prompt' } );
	}

	function getConfirmButton() {
		return screen.getByRole( 'button', { name: 'Change budget' } );
	}

	function getCancelButton() {
		return screen.getByRole( 'button', { name: 'Cancel' } );
	}

	beforeEach( () => {
		fetchAdsIncentiveCredits = jest
			.fn()
			.mockName( 'fetchAdsIncentiveCredits' )
			.mockResolvedValue( {
				adsCurrency: 'USD',
				currency: 'USD',
				spending: 500,
			} );
		useAppDispatch.mockReturnValue( { fetchAdsIncentiveCredits } );

		onResolved = jest.fn();

		Wrapper = () => {
			const ref = useRef();

			const handleClick = ( e ) => {
				const dailyBudget = Number( e.target.value );
				ref.current.resolve( dailyBudget ).then( onResolved );
			};

			return (
				<div>
					<BudgetIncentivePrompt
						countryCodes={ [ 'US' ] }
						ref={ ref }
					/>
					<button onClick={ handleClick } value="8">
						Prompt
					</button>
				</div>
			);
		};
	} );

	it( 'should not render a prompt by default', async () => {
		render( <Wrapper /> );
		await waitFor( () =>
			expect( fetchAdsIncentiveCredits ).toHaveBeenCalledTimes( 1 )
		);

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		expect( getPromptButton() ).toBeInTheDocument();
	} );

	describe( 'After calling to `ref.current.resolve()`', () => {
		it( 'should render a prompt', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

			await user.click( getPromptButton() );

			expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
			expect(
				screen.getByText( 'This offer won’t last long!' )
			).toBeInTheDocument();
			expect( getCancelButton() ).toBeInTheDocument();
			expect( getConfirmButton() ).toBeInTheDocument();
		} );

		it( 'should not render a prompt if calling with a daily budget higher than or equal to the default daily budget', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

			const promptButton = getPromptButton();
			promptButton.setAttribute( 'value', '9' );
			await user.click( promptButton );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

			promptButton.setAttribute( 'value', '8.34' );
			await user.click( promptButton );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

			promptButton.setAttribute( 'value', '8.33' );
			await user.click( promptButton );

			expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
		} );

		it( 'should not render a prompt if the spending is different from the Ads currency', async () => {
			fetchAdsIncentiveCredits.mockResolvedValue( {
				adsCurrency: 'EUR',
				currency: 'USD',
				spending: 500,
			} );

			const user = userEvent.setup();
			render( <Wrapper /> );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

			await user.click( getPromptButton() );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		} );

		it( 'should not render a prompt if unable to retrieve the spending', async () => {
			fetchAdsIncentiveCredits.mockRejectedValue(
				new Error( 'JS test' )
			);

			const user = userEvent.setup();
			render( <Wrapper /> );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

			await user.click( getPromptButton() );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		} );

		it( 'should not render budget recommendation options', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );

			expect(
				screen.queryByRole( 'radio', {
					name: /^(high|recommended|low)$/,
				} )
			).not.toBeInTheDocument();
		} );

		it( 'should divide the spending of the incentive credits by 60 as the default daily budget', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );

			expect(
				screen.getByText(
					'Increase your budget to $8.34 and get it all back in FREE AD CREDIT*!'
				)
			).toBeInTheDocument();
		} );

		it( 'should pre-populate the default daily budget into the input field', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );

			const input = screen.getByRole( 'textbox' );
			expect( input ).toBeInTheDocument();
			expect( input ).toHaveValue( '8.34' );
		} );

		it( 'should disable the confirm button if the entered value is less than the default daily budget', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );
			const button = getConfirmButton();

			expect( button ).toBeEnabled();

			const input = screen.getByRole( 'textbox' );
			await user.clear( input );

			expect( button ).toBeDisabled();

			await user.type( input, '8.33' );

			expect( button ).toBeDisabled();

			await user.click( input );
			await user.keyboard( '{End}' );
			await user.keyboard( '{Backspace}' );
			await user.type( input, '4' );

			expect( button ).toBeEnabled();
		} );

		it( 'should resolve the callback with the pre-populated default daily budget after confirming', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );

			expect( onResolved ).not.toHaveBeenCalled();

			await user.click( getConfirmButton() );

			expect( onResolved ).toHaveBeenCalledTimes( 1 );
			expect( onResolved ).toHaveBeenCalledWith( 8.34 );
		} );

		it( 'should resolve the callback with the entered daily budget after confirming', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );

			const input = screen.getByRole( 'textbox' );
			await user.clear( input );
			await user.type( input, '9.99' );

			expect( onResolved ).not.toHaveBeenCalled();

			await user.click( getConfirmButton() );

			expect( onResolved ).toHaveBeenCalledTimes( 1 );
			expect( onResolved ).toHaveBeenCalledWith( 9.99 );
		} );

		it( 'should resolve the callback with a `NaN` after canceling', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );

			expect( onResolved ).not.toHaveBeenCalled();

			await user.click( getCancelButton() );

			expect( onResolved ).toHaveBeenCalledTimes( 1 );
			expect( onResolved ).toHaveBeenCalledWith( NaN );
		} );

		it( 'should resolve the callback with a `NaN` after dismissing', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );

			expect( onResolved ).not.toHaveBeenCalled();

			await user.keyboard( '{Escape}' );

			expect( onResolved ).toHaveBeenCalledTimes( 1 );
			expect( onResolved ).toHaveBeenCalledWith( NaN );
		} );
	} );

	describe( 'Once it has been resolved ...', () => {
		it( 'with confirmation, it should directly resolve the subsequent calls with a `null` without further prompting', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );
			await user.click( getConfirmButton() );
			await user.click( getPromptButton() );
			await user.click( getPromptButton() );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
			expect( onResolved ).toHaveBeenCalledTimes( 3 );
			expect( onResolved ).toHaveBeenNthCalledWith( 1, 8.34 );
			expect( onResolved ).toHaveBeenNthCalledWith( 2, null );
			expect( onResolved ).toHaveBeenNthCalledWith( 3, null );
		} );

		it( 'with cancellation, it should directly resolve the subsequent calls with a `null` without further prompting', async () => {
			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );
			await user.click( getCancelButton() );
			await user.click( getPromptButton() );
			await user.click( getPromptButton() );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
			expect( onResolved ).toHaveBeenCalledTimes( 3 );
			expect( onResolved ).toHaveBeenNthCalledWith( 1, NaN );
			expect( onResolved ).toHaveBeenNthCalledWith( 2, null );
			expect( onResolved ).toHaveBeenNthCalledWith( 3, null );
		} );

		it( 'due to spending retrieval failure, it should directly resolve the subsequent calls with a `null` without further prompting', async () => {
			fetchAdsIncentiveCredits.mockRejectedValue(
				new Error( 'JS test' )
			);

			const user = userEvent.setup();
			render( <Wrapper /> );

			await user.click( getPromptButton() );
			await user.click( getPromptButton() );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
			expect( onResolved ).toHaveBeenCalledTimes( 2 );
			expect( onResolved ).toHaveBeenNthCalledWith( 1, null );
			expect( onResolved ).toHaveBeenNthCalledWith( 2, null );
		} );
	} );
} );
