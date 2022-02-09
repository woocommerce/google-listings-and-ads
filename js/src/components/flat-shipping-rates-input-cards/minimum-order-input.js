/**
 * Internal dependencies
 */
import AppInputPriceControl from '.~/components/app-input-price-control';
import MinimumOrderInputLabel from './minimum-order-input-label';

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
			label={ <MinimumOrderInputLabel countries={ countries } /> }
			suffix={ currency }
			value={ threshold }
			onBlur={ handleBlur }
		/>
	);
};

export default MinimumOrderInput;
