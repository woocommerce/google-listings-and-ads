/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import AppSelectControl from '.~/components/app-select-control';
import useMappingAttributes from '.~/hooks/useMappingAttributes';
import useMappingAttributesSources from '.~/hooks/useMappingAttributesSources';

/**
 * Renders a modal showing a form for editing or creating an Attribute Mapping rule
 *
 * @param {Object} props React props
 * @param {Object} [props.rule] Optional rule to manage
 */
const AttributeMappingRuleModal = ( { rule } ) => {
	const [ selectedAttribute, setSelectedAttribute ] = useState();
	const { data: attributes } = useMappingAttributes();
	const { data: sources } = useMappingAttributesSources( selectedAttribute );

	const handleAttributeChange = ( attribute ) => {
		setSelectedAttribute( attribute );
	};

	return (
		<AppModal
			className="gla-attribute-mapping__rule-modal"
			title={
				rule
					? __( 'Manage attribute rule', ' google-listings-and-ads' )
					: __( 'Create attribute rule', ' google-listings-and-ads' )
			}
			buttons={ [
				<AppButton
					key="save-rule"
					isPrimary
					text={ __( 'Save rule', 'google-listings-and-ads' ) }
					eventName="gla_attribute_mapping_save_rule"
					eventProps={ {
						context: 'attribute-mapping-rule-modal',
					} }
				/>,
			] }
		>
			<AppSelectControl
				label={ __( 'Target attribute', 'google-listings-and-ads' ) }
				onChange={ handleAttributeChange }
				options={ attributes.map( ( attribute ) => {
					return {
						value: attribute.id,
						label: attribute.label,
					};
				} ) }
			/>

			{ sources.length > 0 && (
				<AppSelectControl
					options={ sources.map( ( source ) => {
						return {
							...source,
							disabled: source.value.includes( 'disabled:' ),
						};
					} ) }
				/>
			) }
		</AppModal>
	);
};

export default AttributeMappingRuleModal;
