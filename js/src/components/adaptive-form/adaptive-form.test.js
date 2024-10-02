/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, act, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AdaptiveForm from './adaptive-form';

const alwaysValid = () => ( {} );

const delayOneSecond = () => new Promise( ( r ) => setTimeout( r, 1000 ) );

const setupUserWithFakeTimers = () => {
	jest.useFakeTimers();
	return userEvent.setup( { advanceTimers: jest.advanceTimersByTime } );
};

describe( 'AdaptiveForm', () => {
	afterEach( () => {
		jest.useRealTimers();
		jest.clearAllTimers();
	} );

	it( 'Should have `formContext.adapter` with functions and initial states', () => {
		const children = jest.fn();

		render(
			<AdaptiveForm validate={ alwaysValid }>{ children }</AdaptiveForm>
		);

		const formContextSchema = expect.objectContaining( {
			adapter: expect.objectContaining( {
				isSubmitting: false,
				isSubmitted: false,
				submitter: null,
				validationRequestCount: 0,
				requestedShowValidation: false,
				showValidation: expect.any( Function ),
				hideValidation: expect.any( Function ),
			} ),
		} );

		expect( children ).toHaveBeenLastCalledWith( formContextSchema );
	} );

	it( 'Should provide `isSubmitting` and `isSubmitted` states via adapter', async () => {
		const user = setupUserWithFakeTimers();
		const inspect = jest.fn();

		render(
			<AdaptiveForm validate={ alwaysValid } onSubmit={ delayOneSecond }>
				{ ( formContext ) => {
					const { isSubmitting, isSubmitted } = formContext.adapter;
					inspect( isSubmitting, isSubmitted );

					return <button onClick={ formContext.handleSubmit } />;
				} }
			</AdaptiveForm>
		);

		expect( inspect ).toHaveBeenLastCalledWith( false, false );

		await user.click( screen.getByRole( 'button' ) );

		expect( inspect ).toHaveBeenLastCalledWith( true, false );

		await act( async () => {
			jest.runOnlyPendingTimers();
		} );

		expect( inspect ).toHaveBeenLastCalledWith( false, true );
	} );

	it( 'Should be able to signal failed submission to reset `isSubmitting` and `isSubmitted` states', async () => {
		const user = setupUserWithFakeTimers();
		const inspect = jest.fn();

		const onSubmit = ( values, enhancer ) => {
			enhancer.signalFailedSubmission();
			return delayOneSecond();
		};

		render(
			<AdaptiveForm validate={ alwaysValid } onSubmit={ onSubmit }>
				{ ( formContext ) => {
					const { isSubmitting, isSubmitted } = formContext.adapter;
					inspect( isSubmitting, isSubmitted );

					return <button onClick={ formContext.handleSubmit } />;
				} }
			</AdaptiveForm>
		);

		await user.click( screen.getByRole( 'button' ) );

		expect( inspect ).toHaveBeenLastCalledWith( true, false );

		await act( async () => {
			jest.runOnlyPendingTimers();
		} );

		expect( inspect ).toHaveBeenLastCalledWith( false, false );
	} );

	it( 'Should provide the element triggering the form submission via `submitter` until the processing is completed', async () => {
		const user = setupUserWithFakeTimers();
		const inspectOnSubmit = jest.fn( delayOneSecond );
		const inspectSubmitter = jest.fn();

		render(
			<AdaptiveForm validate={ alwaysValid } onSubmit={ inspectOnSubmit }>
				{ ( formContext ) => {
					inspectSubmitter( formContext.adapter.submitter );

					return (
						<>
							<button onClick={ formContext.handleSubmit }>
								A
							</button>

							<button onClick={ formContext.handleSubmit }>
								B
							</button>
							<button
								onClick={ () => {
									// To simulate that `handleSubmit` is called without passing an `event`.
									formContext.handleSubmit();
								} }
							>
								C
							</button>
						</>
					);
				} }
			</AdaptiveForm>
		);

		const [ buttonA, buttonB, buttonC ] = screen.getAllByRole( 'button' );

		expect( inspectOnSubmit ).toHaveBeenCalledTimes( 0 );

		// Click button A to test if the form indicates that button A triggered a submission.
		await user.click( buttonA );
		await act( async () => {
			jest.runOnlyPendingTimers();
		} );

		expect( inspectSubmitter ).toHaveBeenCalledWith( buttonA );
		expect( inspectSubmitter ).toHaveBeenLastCalledWith( null );
		expect( inspectOnSubmit ).toHaveBeenCalledTimes( 1 );
		expect( inspectOnSubmit ).toHaveBeenLastCalledWith(
			{},
			expect.objectContaining( { submitter: buttonA } )
		);

		// Click button B to test if the form indicates that button B triggered another submission.
		inspectSubmitter.mockClear();

		await user.click( buttonB );
		await act( async () => {
			jest.runOnlyPendingTimers();
		} );

		expect( inspectSubmitter ).toHaveBeenCalledWith( buttonB );
		expect( inspectSubmitter ).toHaveBeenLastCalledWith( null );
		expect( inspectOnSubmit ).toHaveBeenCalledTimes( 2 );
		expect( inspectOnSubmit ).toHaveBeenLastCalledWith(
			{},
			expect.objectContaining( { submitter: buttonB } )
		);

		// Click button C to test if the form stays `submitter` as `null` if `handleSubmit`
		// is triggered without a corresponding event.
		inspectSubmitter.mockClear();

		await user.click( buttonC );

		expect( inspectSubmitter ).toHaveBeenCalled();
		inspectSubmitter.mock.calls.forEach( ( args ) => {
			expect( args ).toEqual( [ null ] );
		} );
		expect( inspectOnSubmit ).toHaveBeenCalledTimes( 3 );
		expect( inspectOnSubmit ).toHaveBeenLastCalledWith(
			{},
			expect.objectContaining( { submitter: null } )
		);
	} );

	it( 'Should be able to accumulate and reset the validation request count and requested state', async () => {
		const user = userEvent.setup();
		const inspect = jest.fn();

		render(
			<AdaptiveForm validate={ alwaysValid }>
				{ ( { adapter } ) => {
					inspect(
						adapter.requestedShowValidation,
						adapter.validationRequestCount
					);

					return (
						<>
							<button onClick={ adapter.showValidation }>
								request
							</button>

							<button onClick={ adapter.hideValidation }>
								reset
							</button>
						</>
					);
				} }
			</AdaptiveForm>
		);

		const requestButton = screen.getByRole( 'button', { name: 'request' } );
		const resetButton = screen.getByRole( 'button', { name: 'reset' } );

		expect( inspect ).toHaveBeenLastCalledWith( false, 0 );

		await user.click( requestButton );

		expect( inspect ).toHaveBeenLastCalledWith( true, 1 );

		await user.click( requestButton );

		expect( inspect ).toHaveBeenLastCalledWith( true, 2 );

		await user.click( resetButton );

		expect( inspect ).toHaveBeenLastCalledWith( false, 0 );
	} );

	describe( 'Compatibility patches', () => {
		it( 'Should update all changes to values for the synchronous multiple calls to `setValue`', async () => {
			const user = userEvent.setup();

			render(
				<AdaptiveForm
					initialValues={ {
						firstName: 'Foo',
						lastName: 'Bar',
						email: '(empty)',
					} }
					validate={ alwaysValid }
				>
					{ ( { setValue, values } ) => {
						return (
							<>
								<button
									onClick={ () => {
										setValue( 'firstName', 'Hey' );
										setValue( 'lastName', 'Howdy' );
										setValue( 'email', 'hi[at]greetings' );
									} }
								/>
								<article>
									{ `${ values.firstName } ${ values.lastName } ${ values.email }` }
								</article>
							</>
						);
					} }
				</AdaptiveForm>
			);

			const article = screen.getByRole( 'article' );

			expect( article.textContent ).toBe( 'Foo Bar (empty)' );

			await user.click( screen.getByRole( 'button' ) );

			await waitFor( () =>
				expect( article.textContent ).toBe(
					'Hey Howdy hi[at]greetings'
				)
			);
		} );

		it( 'Should call back to `onChange` for the changed value only', async () => {
			const user = setupUserWithFakeTimers();
			const onChange = jest.fn();

			render(
				<AdaptiveForm
					onChange={ onChange }
					initialValues={ {
						firstName: '',
						lastName: '',
						agreedTerms: false,
					} }
					validate={ alwaysValid }
				>
					{ ( { setValue, getInputProps } ) => {
						return (
							<>
								<button
									onClick={ () => {
										setValue( 'firstName', 'Hey' );
									} }
								/>
								<input
									type="text"
									{ ...getInputProps( 'lastName' ) }
								/>
								<input
									type="checkbox"
									{ ...getInputProps( 'agreedTerms' ) }
								/>
							</>
						);
					} }
				</AdaptiveForm>
			);

			await user.click( screen.getByRole( 'button' ) );
			await act( async () => {
				jest.runAllTimers();
			} );

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( onChange ).toHaveBeenLastCalledWith(
				{ name: 'firstName', value: 'Hey' },
				expect.any( Object ),
				true
			);

			await user.type( screen.getByRole( 'textbox' ), 'a' );

			expect( onChange ).toHaveBeenCalledTimes( 2 );
			expect( onChange ).toHaveBeenLastCalledWith(
				{ name: 'lastName', value: 'a' },
				expect.any( Object ),
				true
			);

			await user.click( screen.getByRole( 'checkbox' ) );

			expect( onChange ).toHaveBeenCalledTimes( 3 );
			expect( onChange ).toHaveBeenLastCalledWith(
				{ name: 'agreedTerms', value: true },
				expect.any( Object ),
				true
			);
		} );
	} );
} );
