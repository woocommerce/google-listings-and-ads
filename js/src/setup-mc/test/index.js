/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { SetupMC } from '../';
describe( 'SetupMC', () => {
	test( 'should render empty message when there are no rows', () => {
		const { container } = render( <SetupMC /> );

		expect( container ).toMatchSnapshot();
	} );
} );
