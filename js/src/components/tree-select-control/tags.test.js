/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Tags from '.~/components/tree-select-control/tags';

const tags = [
	{ id: 'ES', label: 'Spain' },
	{ id: 'FR', label: 'France' },
];

describe( 'TreeSelectControl - Tags Component', () => {
	it( 'Shows all tags by default', () => {
		const { queryAllByRole } = render( <Tags tags={ tags } /> );

		expect( queryAllByRole( 'button' ).length ).toBe( 2 );
	} );

	it( 'Limit Tags visibility', () => {
		const { queryByText } = render(
			<Tags tags={ tags } maxVisibleTags={ 1 } />
		);

		expect( queryByText( 'Spain' ) ).toBeTruthy();
		expect( queryByText( 'France' ) ).toBeFalsy();

		const showMore = queryByText( '+ 1 More' );
		expect( queryByText( 'Show less' ) ).toBeFalsy();
		expect( showMore ).toBeTruthy();
		fireEvent.click( showMore );

		expect( queryByText( 'Spain' ) ).toBeTruthy();
		expect( queryByText( 'France' ) ).toBeTruthy();

		expect( queryByText( 'Show less' ) ).toBeTruthy();
		fireEvent.click( showMore );

		expect( queryByText( 'Spain' ) ).toBeTruthy();
		expect( queryByText( 'France' ) ).toBeFalsy();

		expect( queryByText( 'Show less' ) ).toBeFalsy();
		expect( queryByText( '+ 1 More' ) ).toBeTruthy();
	} );
} );
