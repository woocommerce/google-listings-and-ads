/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import CountriesTimeInput from '~/components/countries-time-input';

/**
 * Form control to edit shipping time settings.
 */
const ShippingTimeSetup = () => {
	const {
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();

	return (
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping times',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>

				<CountriesTimeInput />

				{ renderRequestedValidation( 'flat_shipping_times' ) }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ShippingTimeSetup;
