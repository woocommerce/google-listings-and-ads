/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
// import AppCountryMultiSelect from '.~/components/app-country-multi-select';

const AudienceSection = () => {
	return (
		<Section
			title={ __( 'Audience', 'google-listings-and-ads' ) }
			description={
				<p>
					{ __(
						'Choose where do you want your product ads to appear.',
						'google-listings-and-ads'
					) }
				</p>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					{ /* TODO: labels and helptext. */ }
					{ /* TODO: better component composition to use country single select. */ }
					{ /* <AppCountryMultiSelect multiple={ false } /> */ }
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default AudienceSection;
