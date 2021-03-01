/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import VerticalGapLayout from '../../components/vertical-gap-layout';
import AddTimeButton from './add-time-button';
import CountriesTimeInput from './countries-time-input';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import AppSpinner from '.~/components/app-spinner';

const formKeys = {
	rows: 'shippingTimeOption-rows',
};

const ShippingTimeSetup = ( props ) => {
	const {
		formProps: { getInputProps, values, setValue },
	} = props;

	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const expectedCountryCount = selectedCountryCodes.length;
	const actualCountryCount = values[ formKeys.rows ].reduce( ( acc, cur ) => {
		return acc + cur.countries.length;
	}, 0 );

	const handleCountriesTimeChange = ( idx ) => ( countriesPrice ) => {
		const newValues = [ ...values[ formKeys.rows ] ];
		newValues[ idx ] = countriesPrice;
		setValue( formKeys.rows, newValues );
	};

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<div className="countries-price">
					<VerticalGapLayout>
						{ values[ formKeys.rows ].map( ( el, idx ) => {
							return (
								<div
									key={ idx }
									className="countries-price-input"
								>
									<CountriesTimeInput
										value={ el }
										onChange={ handleCountriesTimeChange(
											idx
										) }
									/>
								</div>
							);
						} ) }
						{ actualCountryCount < expectedCountryCount && (
							<div className="add-rate-button">
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
