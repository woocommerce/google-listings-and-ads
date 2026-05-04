/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import SupportedCountrySelect from '~/components/supported-country-select';

const EditPrimaryFeed = () => {
	const {
		getInputProps,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();

	return (
		<div className="gla-edit-primary-feed">
			<SupportedCountrySelect
				multiple
				{ ...getInputProps( 'countries' ) }
				help={ __(
					'Select which countries your store ships to.',
					'google-listings-and-ads'
				) }
			/>

			{ renderRequestedValidation( 'countries' ) }
		</div>
	);
};

export default EditPrimaryFeed;
