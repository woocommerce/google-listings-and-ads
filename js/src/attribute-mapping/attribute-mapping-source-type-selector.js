/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Subsection from '.~/wcdl/subsection';
import AppRadioContentControl from '.~/components/app-radio-content-control';
import AppInputControl from '.~/components/app-input-control';
import AttributeMappingFieldSourcesControl from '.~/attribute-mapping/attribute-mapping-field-sources-control';
import AppDocumentationLink from '.~/components/app-documentation-link';
import HelpPopover from '.~/components/help-popover';

const SOURCE_TYPES = {
	FIXED: 'fixed',
	FIELD: 'field',
};

/**
 * Renders a selector for choosing the source field.
 *
 * @param { Object } props The component props
 * @param { Array } props.sources The sources available for the selector
 */

const AttributeMappingSourceTypeSelector = ( { sources = [] } ) => {
	const [ sourceType, setSourceType ] = useState( SOURCE_TYPES.FIELD );

	return (
		<>
			<Subsection.Subtitle className="gla_attribute_mapping_helper-text">
				{ __(
					'Choose how to assign a value to the target attribute',
					'google-listings-and-ads'
				) }
			</Subsection.Subtitle>
			<AppRadioContentControl
				className="gla-attribute-mapping__radio-control"
				label={
					<>
						{ __(
							'Use value from existing product field.',
							'google-listings-and-ads'
						) }
						<HelpPopover
							id={ `${ SOURCE_TYPES.FIELD }-helper-popover` }
						>
							{ createInterpolateElement(
								__(
									'Create a connection between the target attribute and an existing product field to auto-populate the target attribute with with the value of the field it is linked to. <link>Learn more</link>',
									'google-listings-and-ads'
								),
								{
									link: (
										<AppDocumentationLink
											context="attribute-mapping"
											linkId="learn-more-about-field-values"
											href="https://example.com" // todo: check link
										/>
									),
								}
							) }
						</HelpPopover>
					</>
				}
				onChange={ setSourceType }
				value={ SOURCE_TYPES.FIELD }
				selected={ sourceType }
				collapsible
			>
				<AttributeMappingFieldSourcesControl
					sources={ sources }
					help={
						<Subsection.HelperText className="gla-attribute-mapping__help-text">
							{ createInterpolateElement(
								__(
									'Can’t find an appropriate field?. <link>Create a new attribute</link>',
									'google-listings-and-ads'
								),
								{
									link: (
										<AppDocumentationLink
											context="attribute-mapping"
											linkId="create-new-attribute"
											href="/wp-admin/edit.php?post_type=product&page=product_attributes"
										/>
									),
								}
							) }
						</Subsection.HelperText>
					}
				/>
			</AppRadioContentControl>
			<AppRadioContentControl
				className="gla-attribute-mapping__radio-control"
				label={
					<>
						{ __(
							'Set a fixed value.',
							'google-listings-and-ads'
						) }
						<HelpPopover
							id={ `${ SOURCE_TYPES.FIXED }-helper-popover` }
						>
							{ createInterpolateElement(
								__(
									'Use fixed values to populate the target attribute with a value you specify. For example, you can enter a fixed value of White to specify a single color for all your products. <link>Learn more about fixed values</link>',
									'google-listings-and-ads'
								),
								{
									link: (
										<AppDocumentationLink
											context="attribute-mapping"
											linkId="clearn-more-about-fixed-values"
											href="https://example.com" // Todo: Check link
										/>
									),
								}
							) }
						</HelpPopover>
					</>
				}
				onChange={ setSourceType }
				value={ SOURCE_TYPES.FIXED }
				selected={ sourceType }
				collapsible
			>
				<AppInputControl
					placeholder={ __(
						'Enter a value',
						'google-listings-and-ads'
					) }
				/>
			</AppRadioContentControl>
		</>
	);
};

export default AttributeMappingSourceTypeSelector;
