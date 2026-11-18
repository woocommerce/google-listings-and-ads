/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Body from './body';

describe( 'Section.Card.Body', () => {
	it( 'Should not add a padding modifier class by default', () => {
		render( <Body>Content</Body> );

		const body = screen.getByText( 'Content' );

		expect( body ).toHaveClass( 'gla-section-card-body' );
		expect( body ).not.toHaveClass(
			'gla-section-card-body--padding-medium'
		);
	} );

	it( 'Should add the medium padding class when `padding` is "medium"', () => {
		render( <Body padding="medium">Content</Body> );

		expect( screen.getByText( 'Content' ) ).toHaveClass(
			'gla-section-card-body',
			'gla-section-card-body--padding-medium'
		);
	} );

	it( 'Should keep the custom class name and not pass `padding` to the DOM', () => {
		render(
			<Body padding="medium" className="custom-class">
				Content
			</Body>
		);

		const body = screen.getByText( 'Content' );

		expect( body ).toHaveClass( 'custom-class' );
		expect( body ).not.toHaveAttribute( 'padding' );
	} );
} );
