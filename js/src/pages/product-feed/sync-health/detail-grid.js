/**
 * Internal dependencies
 */
import './detail-grid.scss';

/**
 * Compact label/value pairs that flow into as many columns as the width allows.
 * Shared by the Overview's sync times and the Advanced troubleshooting details.
 *
 * @param {Object} props
 * @param {Array<[string, JSX.Element|string]>} props.rows Label and value pairs.
 */
const DetailGrid = ( { rows } ) => (
	<dl className="gla-detail-grid">
		{ rows.map( ( [ label, value ] ) => (
			<div key={ label } className="gla-detail-grid__item">
				<dt>{ label }</dt>
				<dd>{ value }</dd>
			</div>
		) ) }
	</dl>
);

export default DetailGrid;
