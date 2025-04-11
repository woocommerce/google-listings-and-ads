/**
 * @typedef {import('./index.js').Segment} Segment
 */

/**
 * Renders a legend for the horizontal stacked bar chart.
 * Displays color indicators and percentage labels for each segment.
 *
 * @param {Object} props - Component props
 * @param {Array<Segment>} props.segments - Array of segments to display in the legend
 * @return {JSX.Element} The rendered legend component with color indicators and labels
 */
const Legend = ( { segments } ) => {
	return (
		<ul>
			{ segments.map( ( segment ) => {
				return (
					<li
						key={ segment.id }
						className="gla-horizontal-stacked-bar__legend-item"
					>
						<span
							className="gla-horizontal-stacked-bar__legend-color"
							style={ {
								backgroundColor: segment.color,
							} }
						/>
						{ `${ segment.percentage }% ${ segment.label }` }
					</li>
				);
			} ) }
		</ul>
	);
};

export default Legend;
