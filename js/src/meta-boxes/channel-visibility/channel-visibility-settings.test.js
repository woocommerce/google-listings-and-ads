/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ChannelVisibilitySettings from './channel-visibility-settings';

const FIELD_ID = 'gla_channel_visibility';

let mockGlaData;

jest.mock( '~/constants', () => ( {
	get glaData() {
		return mockGlaData;
	},
} ) );

const getHiddenInput = ( container ) =>
	container.querySelector( `input[type="hidden"][name="${ FIELD_ID }"]` );

describe( 'ChannelVisibilitySettings', () => {
	test( 'Toggle is checked by default when channel_visibility is sync-and-show', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'sync-and-show',
				product_is_visible: true,
			},
		};
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	test( 'Toggle is unchecked by default when channel_visibility is dont-sync-and-show', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'dont-sync-and-show',
				product_is_visible: true,
			},
		};
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	test( 'Clicking the toggle switches the checked state', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'sync-and-show',
				product_is_visible: true,
			},
		};
		render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox' );
		expect( toggle ).toBeChecked();

		fireEvent.click( toggle );
		expect( toggle ).not.toBeChecked();

		fireEvent.click( toggle );
		expect( toggle ).toBeChecked();
	} );

	test( 'Toggle is checked by default when channel_visibility is unset', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: null,
				product_is_visible: true,
			},
		};
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	test( 'Toggle is disabled and unchecked when the product is not visible', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'sync-and-show',
				product_is_visible: false,
			},
		};
		const { container } = render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox' );
		expect( toggle ).toBeDisabled();
		expect( toggle ).not.toBeChecked();
		expect( getHiddenInput( container ) ).toBeDisabled();
	} );
} );
