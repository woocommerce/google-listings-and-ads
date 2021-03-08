/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import SupportedCountrySelect from '.~/components/supported-country-select';

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
					<SupportedCountrySelect
						label={ __(
							'Select one country',
							'google-listings-and-ads'
						) }
						helperText={ __(
							'You can only select one country per campaign. ',
							'google-listings-and-ads'
						) }
					/>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default AudienceSection;
