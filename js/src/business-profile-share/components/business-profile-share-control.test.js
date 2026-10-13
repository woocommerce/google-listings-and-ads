/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import BusinessProfileShareControl from './business-profile-share-control';

const UPDATE_WARNING =
	'Updating this post on Google sends it back for review. If Google rejects the update, the post is hidden from Search and Maps.';

/**
 * Renders the control with sensible defaults, overridden by the given props.
 *
 * @param {Object} props Props to override.
 * @return {Object} The callbacks passed in.
 */
function renderControl( props = {} ) {
	const callbacks = {
		onShareEnabledChange: jest.fn().mockName( 'onShareEnabledChange' ),
		onSummaryChange: jest.fn().mockName( 'onSummaryChange' ),
		getGeneratedSummary: jest
			.fn()
			.mockName( 'getGeneratedSummary' )
			.mockReturnValue( 'Generated text' ),
	};

	render(
		<BusinessProfileShareControl
			locationName="Trailmark Athletics"
			isShareEnabled={ false }
			summary=""
			{ ...callbacks }
			{ ...props }
		/>
	);

	return callbacks;
}

describe( 'BusinessProfileShareControl', () => {
	it( 'shows the toggle off, naming the connected location, with no field', () => {
		renderControl();

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).not.toBeChecked();
		expect(
			screen.getByText(
				'Share to Trailmark Athletics as an update when you publish.'
			)
		).toBeInTheDocument();
		expect( screen.queryByRole( 'textbox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Generate from post' } )
		).not.toBeInTheDocument();
	} );

	it( 'reports the toggle being turned on', async () => {
		const user = userEvent.setup();
		const { onShareEnabledChange } = renderControl();

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		);

		expect( onShareEnabledChange ).toHaveBeenCalledWith( true );
	} );

	it( 'shows the field and "Generate from post" while sharing is on', () => {
		renderControl( { isShareEnabled: true, summary: 'My text' } );

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toHaveValue( 'My text' );
		expect(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		).toBeInTheDocument();
	} );

	it( 'fills the field from the saved post with "Generate from post"', async () => {
		const user = userEvent.setup();
		const { onSummaryChange } = renderControl( { isShareEnabled: true } );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect( onSummaryChange ).toHaveBeenCalledWith( 'Generated text' );
	} );

	it( 'warns that updating sends the post back for review once it has been shared', () => {
		renderControl( { isShared: true } );

		expect( screen.getByText( UPDATE_WARNING ) ).toBeInTheDocument();
		expect(
			screen.queryByText(
				'Share to Trailmark Athletics as an update when you publish.'
			)
		).not.toBeInTheDocument();
	} );

	it( 'links to the post on Google once Google provides a link', () => {
		renderControl( {
			isShared: true,
			searchUrl: 'https://local.google.com/place?id=1&use=posts',
		} );

		expect(
			screen.getByRole( 'link', {
				name: 'View on Google Business Profile (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://local.google.com/place?id=1&use=posts'
		);
	} );

	it( 'shows no link until Google provides one', () => {
		renderControl( { isShared: true, searchUrl: '' } );

		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );

	it( 'disables the toggle, field and button together', () => {
		renderControl( { isShareEnabled: true, disabled: true } );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).toBeDisabled();
		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toBeDisabled();
		expect(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		).toBeDisabled();
	} );
} );
