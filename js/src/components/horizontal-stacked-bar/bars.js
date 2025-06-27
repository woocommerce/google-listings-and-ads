/**
 * @typedef {import('./index.js').Segment} Segment
 */

/**
 * Renders the visual bars portion of the horizontal stacked bar chart.
 * Each segment is represented as a colored span with width proportional to its percentage value.
 *
 * @param {Object} props - Component props
 * @param {Array<Segment>} props.segments - Array of segments to display as bars
 * @return {JSX.Element} The rendered bars component showing proportional colored segments
 */
const Bars = ( { segments } ) => {
	return (
		<>
			{ segments.map( ( segment ) => {
				const label = `${ segment.percentage }% ${ segment.label }`;

				return (
					<span
						key={ segment.id }
						style={ {
							width: `${ segment.percentage }%`,
							backgroundColor: segment.color,
						} }
						aria-label={ label }
						title={ label }
					/>
				);
			} ) }
		</>
	);
};

export default Bars;
