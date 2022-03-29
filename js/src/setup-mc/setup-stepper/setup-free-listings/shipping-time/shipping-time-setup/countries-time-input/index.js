/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppInputNumberControl from '.~/components/app-input-number-control';
import AppSpinner from '.~/components/app-spinner';
import ShippingTimeInputControlLabelText from '.~/components/shipping-time-input-control-label-text';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import EditTimeButton from './edit-time-button';
import './index.scss';

const CountriesTimeInput = ( props ) => {
	const { value, onBlur } = props;
	const { countries, time } = value;
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-countries-time-input">
			<AppInputNumberControl
				label={
					<div className="label">
						<ShippingTimeInputControlLabelText
							countries={ countries }
						/>
						<EditTimeButton time={ value } />
					</div>
				}
				suffix={ __( 'days', 'google-listings-and-ads' ) }
				value={ time }
				onBlur={ onBlur }
			/>
		</div>
	);
};

export default CountriesTimeInput;
