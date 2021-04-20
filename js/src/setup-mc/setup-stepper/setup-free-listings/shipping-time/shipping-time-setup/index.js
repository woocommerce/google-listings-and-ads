/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import AppDocumentationLink from '.~/components/app-documentation-link';
import { STORE_KEY } from '.~/data';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AddTimeButton from './add-time-button';
import getCountriesTimeArray from './getCountriesTimeArray';
import CountriesTimeInputForm from './countries-time-input-form';
import './index.scss';

const ShippingTimeSetup = ( props ) => {
	const {
		formProps: { getInputProps },
	} = props;
	const shippingTimes = useSelect( ( select ) =>
		select( STORE_KEY ).getShippingTimes()
	);
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const expectedCountryCount = selectedCountryCodes.length;
	const actualCountryCount = shippingTimes.length;
	const remainingCount = expectedCountryCount - actualCountryCount;

	const countriesTimeArray = getCountriesTimeArray( shippingTimes );

	// Prefill to-be-added price.
	if ( countriesTimeArray.length === 0 ) {
		countriesTimeArray.push( {
			countries: selectedCountryCodes,
			time: '',
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
				<CheckboxControl
					label={ createInterpolateElement(
						__(
							'I allow Google to collect and calculate my shipping times for more accurate estimates. <link>Learn more</link>',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="setup-mc-shipping-time"
									linkId="shipping-time-allow-google-data-collection"
									href="https://www.google.com/retail/solutions/merchant-center/"
								/>
							),
						}
					) }
					{ ...getInputProps( 'share_shipping_time' ) }
				/>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingTimeSetup;
