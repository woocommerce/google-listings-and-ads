/**
 * Internal dependencies
 */
import AppInputPriceControl from '../app-input-price-control';

const MinimumOrderInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, threshold, currency } = value;

	const handleBlur = ( event, numberValue ) => {
		if ( numberValue === value.threshold ) {
			return;
		}

		onChange( {
			countries,
			threshold: numberValue,
			currency,
		} );
	};

	// TODO: label with edit button.
	return (
		<AppInputPriceControl
			label="Minimum order"
			suffix={ currency }
			value={ threshold }
			onBlur={ handleBlur }
		/>
	);
};

export default MinimumOrderInput;
