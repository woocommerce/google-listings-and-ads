/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AddTimeButton from './add-time-button';
import getCountriesTimeArray from './getCountriesTimeArray';
import CountriesTimeInputForm from './countries-time-input-form';
import './index.scss';

const ShippingTimeSetup = () => {
	const { data: shippingTimes } = useShippingTimes();
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const expectedCountryCount = selectedCountryCodes.length;
	const actualCountryCount = shippingTimes.length;
	const remainingCount = expectedCountryCount - actualCountryCount;

	const countriesTimeArray = getCountriesTimeArray( shippingTimes );

	// Prefill to-be-added time.
	if ( countriesTimeArray.length === 0 ) {
		countriesTimeArray.push( {
			countries: selectedCountryCodes,
			time: null,
		} );
	}

	return (
		<div className="gla-shipping-time-setup">
			<VerticalGapLayout>
				<div className="countries-time">
					<VerticalGapLayout>
						{ countriesTimeArray.map( ( el ) => {
							return (
								<div
									key={ el.countries.join( '-' ) }
									className="countries-time-input-form"
								>
									<CountriesTimeInputForm savedValue={ el } />
								</div>
							);
						} ) }
						{ actualCountryCount >= 1 && remainingCount >= 1 && (
							<div className="add-time-button">
								<AddTimeButton />
							</div>
						) }
					</VerticalGapLayout>
				</div>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingTimeSetup;
