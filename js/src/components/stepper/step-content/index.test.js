/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import StepContent from '.';

describe( 'StepContent', () => {
	it( 'Should not add a variant modifier class by default', () => {
		const { container } = render( <StepContent>Content</StepContent> );

		const stepContent = container.firstChild;

		expect( stepContent ).toHaveClass( 'gla-step-content' );
		expect( stepContent ).not.toHaveClass( 'gla-step-content--narrow' );
	} );

	it( 'Should add the narrow class when `variant` is "narrow"', () => {
		const { container } = render(
			<StepContent variant="narrow">Content</StepContent>
		);

		expect( container.firstChild ).toHaveClass(
			'gla-step-content',
			'gla-step-content--narrow'
		);
	} );

	it( 'Should keep the custom class name and not pass `variant` to the DOM', () => {
		const { container, getByText } = render(
			<StepContent variant="narrow" className="custom-class">
				Content
			</StepContent>
		);

		expect( container.firstChild ).toHaveClass( 'custom-class' );
		expect( container.firstChild ).not.toHaveAttribute( 'variant' );
		expect( getByText( 'Content' ) ).toHaveClass(
			'gla-step-content__container'
		);
	} );
} );
