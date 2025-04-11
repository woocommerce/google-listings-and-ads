/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import HorizontalStackedBar from './index';

describe( 'HorizontalStackedBar', () => {
	test( 'renders null when no segments are provided', () => {
		const { container } = render(
			<HorizontalStackedBar title="Test Chart" segments={ [] } />
		);
		expect( container.firstChild ).toBeNull();
	} );

	test( 'renders the title correctly', () => {
		render(
			<HorizontalStackedBar
				title="Test Chart"
				segments={ [
					{ id: 1, label: 'Segment 1', value: 50, color: 'red' },
					{ id: 2, label: 'Segment 2', value: 50, color: 'blue' },
				] }
			/>
		);
		expect( screen.getByText( 'Test Chart' ) ).toBeInTheDocument();
	} );

	test( 'renders the legend with correct segments', () => {
		render(
			<HorizontalStackedBar
				title="Test Chart"
				segments={ [
					{ id: 1, label: 'Segment 1', value: 30, color: 'red' },
					{ id: 2, label: 'Segment 2', value: 70, color: 'blue' },
				] }
			/>
		);

		expect( screen.getByText( '30% Segment 1' ) ).toBeInTheDocument();
		expect( screen.getByText( '70% Segment 2' ) ).toBeInTheDocument();
	} );

	test( 'renders the chart with correct segment widths', () => {
		render(
			<HorizontalStackedBar
				title="Test Chart"
				segments={ [
					{ id: 1, label: 'Segment 1', value: 25, color: 'red' },
					{ id: 2, label: 'Segment 2', value: 75, color: 'blue' },
				] }
			/>
		);

		const chartSegments = screen.getAllByTitle( /% Segment/ );
		expect( chartSegments[ 0 ] ).toHaveStyle( 'width: 25%' );
		expect( chartSegments[ 0 ] ).toHaveStyle( 'background-color: red' );
		expect( chartSegments[ 1 ] ).toHaveStyle( 'width: 75%' );
		expect( chartSegments[ 1 ] ).toHaveStyle( 'background-color: blue' );
	} );

	test( 'renders the chart with valid segment values', () => {
		render(
			<HorizontalStackedBar
				title="Test Chart"
				segments={ [
					{ id: 1, label: 'Segment 1', value: 25, color: 'red' },
					{ id: 2, label: 'Segment 2', value: 'xyz', color: 'blue' },
				] }
			/>
		);

		const chartSegments = screen.getAllByTitle( /% Segment/ );
		expect( chartSegments[ 0 ] ).toHaveStyle( 'width: 100%' );
		expect( chartSegments[ 0 ] ).toHaveStyle( 'background-color: red' );
	} );
} );
