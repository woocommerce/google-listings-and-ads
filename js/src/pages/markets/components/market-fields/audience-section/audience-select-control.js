/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import SupportedCountrySelect from '~/components/supported-country-select';
import './audience-select-control.scss';

/**
 * Component for editing the primary market's audience (countries) in the Edit Market modal.
 */
const AudienceSelectControl = () => {
	const {
		getInputProps,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();

	const inputProps = getInputProps( 'countries' );

	return (
		<div className="gla-audience-select-control">
			<SupportedCountrySelect
				{ ...inputProps }
				help={ __(
					'Select which countries your store ships to.',
					'google-listings-and-ads'
				) }
				label={ __( 'Audience', 'google-listings-and-ads' ) }
				multiple
			/>

			{ renderRequestedValidation( 'countries' ) }
		</div>
	);
};

export default AudienceSelectControl;
