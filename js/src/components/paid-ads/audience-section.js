/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AudienceCountrySelect from '.~/components/audience-country-select';

const AudienceSection = ( props ) => {
	const {
		formProps: { getInputProps },
		disabled = false,
		countrySelectHelperText,
	} = props;

	const inputProps = getInputProps( 'country' );
	if ( disabled ) {
		inputProps.onChange = undefined;
	}

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
					<AudienceCountrySelect
						label={ __(
							'Select one country',
							'google-listings-and-ads'
						) }
						helperText={ countrySelectHelperText }
						inlineTags={ false }
						disabled={ disabled }
						{ ...inputProps }
					/>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default AudienceSection;
