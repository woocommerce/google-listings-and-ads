/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AssetField from './asset-field';

describe( 'AssetField', () => {
	function getToggleButton() {
		return screen.getByRole( 'button', { name: /toggle/i } );
	}

	function getHelpButton() {
		return screen.getByRole( 'button', { name: /open popover/i } );
	}

	it( 'Should render the heading', () => {
		render( <AssetField heading="Heading" /> );

		expect( screen.getByText( 'Heading' ) ).toBeInTheDocument();
	} );

	it( 'Should render the subheading', () => {
		render( <AssetField subheading="Subheading" /> );

		expect( screen.getByText( 'Subheading' ) ).toBeInTheDocument();
	} );

	it( 'When `numOfIssues` > 0, it should render the number of issues', () => {
		const { rerender } = render( <AssetField numOfIssues={ 1 } /> );

		expect( screen.getByText( '1 issue' ) ).toBeInTheDocument();

		rerender( <AssetField numOfIssues={ 2 } /> );

		expect( screen.getByText( '2 issues' ) ).toBeInTheDocument();
	} );

	it( 'When `numOfIssues` = 0, it should not render the number of issues', () => {
		render( <AssetField numOfIssues={ 0 } /> );

		expect( screen.queryByText( '0 issues' ) ).not.toBeInTheDocument();
	} );

	it( 'When not setting `markOptional`, it should not render the optional label', () => {
		render( <AssetField /> );

		expect( screen.queryByText( '(Optional)' ) ).not.toBeInTheDocument();
	} );

	it( 'When setting `markOptional`, it should render the optional label', () => {
		render( <AssetField markOptional /> );

		expect( screen.getByText( '(Optional)' ) ).toBeInTheDocument();
	} );

	it( 'Regardless of whether it is collapsed or expanded, it should always render the children', () => {
		// In order not to reset children's states.
		const { rerender } = render(
			<AssetField key="1">Children</AssetField>
		);

		expect( screen.queryByText( 'Children' ) ).toBeInTheDocument();

		rerender(
			<AssetField key="2" initialExpanded>
				Children
			</AssetField>
		);

		expect( screen.queryByText( 'Children' ) ).toBeInTheDocument();
	} );

	it( 'When not setting `initialExpanded`, it should collapse to hide the children', () => {
		render( <AssetField>Children</AssetField> );

		expect( screen.queryByText( 'Children' ) ).not.toBeVisible();
	} );

	it( 'When setting `initialExpanded`, it should expand to show the children', () => {
		render( <AssetField initialExpanded>Children</AssetField> );

		expect( screen.getByText( 'Children' ) ).toBeVisible();
	} );

	it( 'When disabling, it should also collapse to hide the children', () => {
		const { rerender } = render(
			<AssetField initialExpanded>Children</AssetField>
		);

		expect( screen.getByText( 'Children' ) ).toBeVisible();

		rerender(
			<AssetField initialExpanded disabled>
				Children
			</AssetField>
		);

		expect( screen.queryByText( 'Children' ) ).not.toBeVisible();
	} );

	it( 'When disabled, it should also disable the toggle button and help button', async () => {
		const { rerender } = render( <AssetField /> );

		expect( getToggleButton() ).toBeEnabled();
		expect( getHelpButton() ).toBeEnabled();

		rerender( <AssetField disabled /> );

		expect( getToggleButton() ).toBeDisabled();
		expect( getHelpButton() ).toBeDisabled();
	} );

	it( 'When clicking on the toggle button, it should toggle to show or hide the children', async () => {
		render( <AssetField>Children</AssetField> );

		expect( screen.queryByText( 'Children' ) ).not.toBeVisible();

		await userEvent.click( getToggleButton() );

		expect( screen.getByText( 'Children' ) ).toBeVisible();

		await userEvent.click( getToggleButton() );

		expect( screen.queryByText( 'Children' ) ).not.toBeVisible();
	} );
} );
