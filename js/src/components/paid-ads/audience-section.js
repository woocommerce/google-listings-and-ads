/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import Section from '.~/wcdl/section';
import AudienceCountrySelect from '.~/components/audience-country-select';
import './audience-section.scss';

function toCountryOptions( countryCodes, countryNameMap ) {
	return countryCodes.map( ( code ) => ( {
		label: countryNameMap[ code ],
		value: code,
	} ) );
}

/**
 * Renders <Section> and <Section.Card> UI with country(s) selector.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {boolean} [props.multiple=true] Whether the selector is multi-selected.
 * @param {boolean} [props.disabled=false] Whether the selector is disabled.
 * @param {JSX.Element} [props.countrySelectHelperText] Helper text to be displayed under the selector.
 */
const AudienceSection = ( props ) => {
	const {
		formProps: { getInputProps },
		multiple = true,
		disabled = false,
		countrySelectHelperText,
	} = props;

	const countryNameMap = useCountryKeyNameMap();
	const inputProps = getInputProps( 'countryCodes' );

	const selector = multiple ? (
		<AudienceCountrySelect
			label={ __( 'Select country/s', 'google-listings-and-ads' ) }
			help={ countrySelectHelperText }
			disabled={ disabled }
			value={ inputProps.value }
			additionalCountryCodes={ disabled ? inputProps.value : undefined }
			onChange={ inputProps.onChange }
		/>
	) : (
		<SelectControl
			label={ __( 'Select one country', 'google-listings-and-ads' ) }
			help={ countrySelectHelperText }
			disabled={ disabled }
			options={ toCountryOptions( inputProps.value, countryNameMap ) }
			value={ inputProps.selected[ 0 ] }
			onChange={ inputProps.onChange }
			role="combobox"
		/>
	);

	return (
		<Section
			className="gla-audience-section"
			disabled={ disabled }
			title={ __( 'Ads audience', 'google-listings-and-ads' ) }
			description={
				<p>
					{ __(
						'Choose where you want your product ads to appear',
						'google-listings-and-ads'
					) }
				</p>
			}
		>
			<Section.Card>
				<Section.Card.Body>{ selector }</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default AudienceSection;
