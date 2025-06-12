/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import DeltaValue from './index';

describe( 'DeltaValue Component', () => {
	it( 'renders a positive value with the correct class and formatting', () => {
		render( <DeltaValue amount={ 10 } /> );
		const spanElement = screen.getByText( '+10' );
		expect( spanElement ).toBeInTheDocument();
		expect(
			spanElement.classList.contains( 'gla-delta-value--positive' )
		).toBeTruthy();
	} );

	it( 'renders a negative value with the correct class and formatting', () => {
		render( <DeltaValue amount={ -5 } /> );
		const spanElement = screen.getByText( '-5' );
		expect( spanElement ).toBeInTheDocument();
		expect(
			spanElement.classList.contains( 'gla-delta-value--negative' )
		).toBeTruthy();
	} );

	it( 'renders zero value without positive or negative class', () => {
		render( <DeltaValue amount={ 0 } /> );
		const spanElement = screen.getByText( '0' );
		expect( spanElement ).toBeInTheDocument();
		expect(
			spanElement.classList.contains( 'gla-delta-value--positive' )
		).toBeFalsy();
		expect(
			spanElement.classList.contains( 'gla-delta-value--negative' )
		).toBeFalsy();
	} );

	it( 'renders a positive value with a suffix', () => {
		render( <DeltaValue amount={ 15 } suffix="%" /> );
		const spanElement = screen.getByText( '+15%' );
		expect( spanElement ).toBeInTheDocument();
	} );

	it( 'renders a negative value with a suffix', () => {
		render( <DeltaValue amount={ -20 } suffix="%" /> );
		const spanElement = screen.getByText( '-20%' );
		expect( spanElement ).toBeInTheDocument();
	} );

	it( 'handles NaN amount gracefully by rendering 0', () => {
		render( <DeltaValue amount={ 'amount' } /> );
		const spanElement = screen.getByText( '0' );
		expect( spanElement ).toBeInTheDocument();
	} );

	it( 'applies the correct default value when no amount is provided', () => {
		render( <DeltaValue /> );
		const spanElement = screen.getByText( '0' );
		expect( spanElement ).toBeInTheDocument();
	} );

	it( 'handles non-string suffix by converting it to a string', () => {
		render( <DeltaValue amount={ 10 } suffix={ 123 } /> );
		const spanElement = screen.getByText( '+10123' );
		expect( spanElement ).toBeInTheDocument();
	} );
} );
