/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Legend from './legend';
import Bars from './bars';
import './index.scss';

/**
 * @typedef {Object} Segment
 * @property {string|number} id - Unique identifier for the segment
 * @property {string} label - Display label for the segment
 * @property {string|number} value - Numeric value determining segment size
 * @property {string} color - CSS color value for the segment
 * @property {number} [percentage] - Calculated percentage of the segment (added during processing)
 */

/**
 * Renders a horizontal stacked bar chart with colored segments and a legend.
 *
 * @param {Object} props - Component props.
 * @param {string} props.title - Title of the chart.
 * @param {Array<Segment>} [props.segments=[]] - Array of data segments to display in the chart.
 * @param {string} [props.className] - Additional CSS class for the component.
 * @return {JSX.Element|null} The rendered component or null if no valid segments.
 */
const HorizontalStackedBar = ( { title, segments, className } ) => {
	if ( ! segments || segments.length === 0 ) {
		return null;
	}

	const validSegments = segments.filter(
		( segment ) => ! isNaN( Number( segment.value ) )
	);

	const total = validSegments.reduce(
		( sum, segment ) => sum + Number( segment.value ),
		0
	);

	const segmentsWithPercentage = validSegments.map( ( segment ) => {
		return {
			...segment,
			percentage: Math.round( ( segment.value / total ) * 100 ),
		};
	} );

	return (
		<div
			className={ classnames( 'gla-horizontal-stacked-bar', className ) }
		>
			<p className="gla-horizontal-stacked-bar__title">{ title }</p>

			<div className="gla-horizontal-stacked-bar__legend">
				<Legend segments={ segmentsWithPercentage } />
			</div>

			<div className="gla-horizontal-stacked-bar__chart">
				<Bars segments={ segmentsWithPercentage } />
			</div>
		</div>
	);
};

export default HorizontalStackedBar;
