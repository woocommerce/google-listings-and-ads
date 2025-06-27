/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import {
	METRIC_TYPE_DELTA,
	METRIC_TYPE_EFFECTIVENESS,
	METRIC_TYPE_PERCENTAGE,
	METRIC_TYPE_PRICE,
} from '../constants';
import DeltaValue from '~/components/delta-value';
import Price from '../price';
import Label from '../label';
import EffectivenessIndicator from '../effectiveness-indicator';
import './metric-value.scss';

/**
 * Component to display a metric value with a label and formatted value based on its type.
 *
 * @param {Object} props - The component props.
 * @param {string} props.labelKey - The key for the label to be displayed.
 * @param {number|string|null|undefined} props.value - The value of the metric to be displayed. If undefined, null, or an empty string, the component returns null.
 * @param {string} props.type - The type of the metric, which determines how the value is formatted.
 *                              Supported types:
 *                              - METRIC_TYPE_PRICE: Formats the value as a price using the <Price> component.
 *                              - METRIC_TYPE_DELTA: Formats the value as a percentage delta using the <DeltaValue> component.
 *                              - METRIC_TYPE_EFFECTIVENESS: Displays an effectiveness indicator using the <EffectivenessIndicator> component.
 *                              - METRIC_TYPE_PERCENTAGE: Formats the value as a percentage string.
 * @param {string} [props.className] - Optional additional class name for the component.
 *
 * @return {JSX.Element|null} A JSX element displaying the metric value with its label, or null if the value is invalid.
 */
const MetricValue = ( { labelKey, value, type, className } ) => {
	if ( value === undefined || value === null || value === '' ) {
		return null;
	}

	if ( type === METRIC_TYPE_PRICE && isNaN( value ) ) {
		return null;
	}

	let formattedValue = value;

	switch ( type ) {
		case METRIC_TYPE_PRICE:
			formattedValue = <Price amount={ value } />;
			break;
		case METRIC_TYPE_DELTA:
			formattedValue = <DeltaValue amount={ value * 100 } suffix="%" />;
			break;
		case METRIC_TYPE_EFFECTIVENESS:
			formattedValue = <EffectivenessIndicator effectiveness={ value } />;
			break;
		case METRIC_TYPE_PERCENTAGE:
			formattedValue = `${ parseInt( value, 10 ) }%`;
			break;
	}

	return (
		<div
			className={ classnames(
				'gla-change-price-modal__metric-value',
				className
			) }
		>
			<div className="gla-change-price-modal__metric-value-title">
				<Label labelKey={ labelKey } />
			</div>
			<div className="gla-change-price-modal__metric-value-value">
				{ formattedValue }
			</div>
		</div>
	);
};

export default MetricValue;
