/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppInputControl from '~/components/app-input-control';
import TextsEditor from './texts-editor';
import AssetField from './asset-field';
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';
import { ASSET_FORM_KEY } from '~/constants';
import {
	ASSET_TEXT_SPECS,
	ASSET_DISPLAY_URL_PATH_SPECS,
} from '../../assetSpecs';

const ctaOptions = [
	{ label: 'Automated', value: '' },
	{ label: 'Learn more', value: 'learn_more' },
	{ label: 'Get quote', value: 'get_quote' },
	{ label: 'Apply now', value: 'apply_now' },
	{ label: 'Sign up', value: 'sign_up' },
	{ label: 'Contact us', value: 'contact_us' },
	{ label: 'Subscribe', value: 'subscribe' },
	{ label: 'Download', value: 'download' },
	{ label: 'Book now', value: 'book_now' },
	{ label: 'Shop now', value: 'shop_now' },
];

/**
 * Renders the text section of the asset group editor, allowing users to input and edit headlines, descriptions,
 * call to action, and display URL path.
 *
 * @param {Object} props - Component props.
 * @param {Object} props.initialValues - Initial values for the asset group text fields.
 * @param {Function} props.refFirstErrorField - Function to bind refs for error fields.
 * @param {boolean} props.isSelectedFinalUrl - Indicates if a final URL is selected.
 * @param {Function} props.getNumOfIssues - Function to get the number of issues for a field.
 * @param {Function} props.renderErrors - Function to render error messages for a field.
 * @param {string} props.finalUrl - The final URL for the ad, used to extract the hostname.
 *
 * @return {JSX.Element} The rendered AssetGroupTextSection component.
 */
const AssetGroupTextSection = ( {
	initialValues,
	refFirstErrorField,
	isSelectedFinalUrl,
	getNumOfIssues,
	renderErrors,
	finalUrl,
} ) => {
	const { values, setValue, getInputProps } = useAdaptiveFormContext();
	const hostname = finalUrl ? new URL( finalUrl ).hostname : '';
	const ctaProps = getInputProps( ASSET_FORM_KEY.CALL_TO_ACTION_SELECTION );

	return (
		<Section
			className="gla-asset-group-section"
			description={
				<div className="gla-asset-group-section__primary-description">
					<p>
						{ __(
							'The minimum requirements:',
							'google-listings-and-ads'
						) }
					</p>
					<ul>
						<li>
							{ __( '3x Headlines ', 'google-listings-and-ads' ) }
						</li>
						<li>
							{ __(
								'1x Long headline',
								'google-listings-and-ads'
							) }
						</li>
						<li>
							{ __(
								'2x Descriptions',
								'google-listings-and-ads'
							) }
						</li>
					</ul>
					<p>
						<AppDocumentationLink
							context="asset-group"
							href="https://support.google.com/google-ads/answer/14528373"
							linkId="asset-group-images-learn-more"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
			title={ __( 'Text', 'google-listings-and-ads' ) }
		>
			<div className="gla-asset-group-section__content">
				{ ASSET_TEXT_SPECS.map( ( spec ) => {
					const initialTexts = [ initialValues[ spec.key ] ].flat();
					const textProps = getInputProps( spec.key );

					return (
						<AssetField
							disabled={ ! isSelectedFinalUrl }
							heading={ spec.heading }
							help={ spec.help }
							initialExpanded={ isSelectedFinalUrl }
							key={ spec.key }
							numOfIssues={ getNumOfIssues( spec.key ) }
							ref={ refFirstErrorField.bind( spec.key ) }
							subheading={
								<>
									{ spec.subheading }
									{ isSelectedFinalUrl &&
										spec.extraSubheading }
								</>
							}
						>
							<TextsEditor
								addButtonText={ spec.addButtonText }
								assetKey={ spec.key }
								finalUrl={ finalUrl }
								generateButtonPluralText={
									spec.generateButtonPluralText
								}
								generateButtonSingularText={
									spec.generateButtonSingularText
								}
								initialTexts={ initialTexts }
								maxCharacterCounts={ spec.maxCharacterCounts }
								maxNumberOfTexts={ spec.max }
								minNumberOfTexts={ spec.min }
								onChange={ ( texts ) => {
									if ( spec.requiredSingleValue ) {
										textProps.onChange( texts[ 0 ] );
									} else {
										textProps.onChange( texts );
									}
								} }
								placeholder={ spec.capitalizedName }
							>
								{ renderErrors( spec.key ) }
							</TextsEditor>
						</AssetField>
					);
				} ) }
				<AssetField
					className="gla-asset-field-call-to-action"
					disabled={ ! isSelectedFinalUrl }
					heading={ __(
						'Call to action',
						'google-listings-and-ads'
					) }
					help={ __(
						'Select a call to action that aligns with your goals, or use automated call to action which allows Google to automatically choose the most relevant call to action for you.',
						'google-listings-and-ads'
					) }
					initialExpanded={ isSelectedFinalUrl }
				>
					<SelectControl
						onChange={ ctaProps.onChange }
						options={ ctaOptions }
						value={ ctaProps.value || ctaOptions[ 0 ].value }
					/>
				</AssetField>
				<AssetField
					className="gla-asset-field-display-url-path"
					disabled={ ! isSelectedFinalUrl }
					heading={ __(
						'Display URL path',
						'google-listings-and-ads'
					) }
					help={
						<>
							<div>
								{ __(
									`The display URL gives potential customers a clear idea of what webpage they'll reach once they click your ad, so your path text should describe your ad's landing page.`,
									'google-listings-and-ads'
								) }
							</div>
							<div>
								{ __(
									`To create your display URL, Google Ads will combine the domain (for example, "www.google.com" in www.google.com/nonprofits) from your final URL and the path text (for example, "nonprofits" in www.google.com/nonprofits).`,
									'google-listings-and-ads'
								) }
							</div>
						</>
					}
					initialExpanded={ isSelectedFinalUrl }
					numOfIssues={ getNumOfIssues(
						ASSET_FORM_KEY.DISPLAY_URL_PATH
					) }
					ref={ refFirstErrorField.bind(
						ASSET_FORM_KEY.DISPLAY_URL_PATH
					) }
					subheading={ hostname }
					markOptional
				>
					{ ASSET_DISPLAY_URL_PATH_SPECS.map( ( spec, index ) => {
						const paths = values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ];

						return (
							<Fragment key={ index }>
								<span className="gla-asset-field-display-url-path__slash">
									/
								</span>
								<AppInputControl
									className="gla-asset-field-display-url-path__text-input"
									kindCharacterCount="google-ads"
									maxCharacterCount={ spec.maxCharacterCount }
									onChange={ ( value ) => {
										const nextValue = paths.slice();
										nextValue[ index ] = value;
										setValue(
											ASSET_FORM_KEY.DISPLAY_URL_PATH,
											nextValue
										);
									} }
									value={ paths[ index ] || '' }
								/>
							</Fragment>
						);
					} ) }
					{ renderErrors( ASSET_FORM_KEY.DISPLAY_URL_PATH ) }
				</AssetField>
			</div>
		</Section>
	);
};

export default AssetGroupTextSection;
