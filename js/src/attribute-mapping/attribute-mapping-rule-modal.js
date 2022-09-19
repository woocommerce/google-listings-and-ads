/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import Subsection from '.~/wcdl/subsection';
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import AppSelectControl from '.~/components/app-select-control';
import useMappingAttributes from '.~/hooks/useMappingAttributes';
import useMappingAttributesSources from '.~/hooks/useMappingAttributesSources';
import AttributeMappingFieldSourcesControl from './attribute-mapping-field-sources-control';
import AttributeMappingSourceTypeSelector from './attribute-mapping-source-type-selector';
import AttributeMappingCategoryControl from '.~/attribute-mapping/attribute-mapping-category-control';

/**
 * Renders a modal showing a form for editing or creating an Attribute Mapping rule
 *
 * @param {Object} props React props
 * @param {Object} [props.rule] Optional rule to manage
 * @param {Function} [props.onRequestClose] Callback on closing the modal
 */
const AttributeMappingRuleModal = ( { rule, onRequestClose = noop } ) => {
	const [ selectedAttribute, setSelectedAttribute ] = useState();
	const [ dropdownVisible, setDropdownVisible ] = useState( false );

	const { data: attributes } = useMappingAttributes();
	const { data: sources } = useMappingAttributesSources( selectedAttribute );
	const isEnum = attributes?.find( ( { id } ) => id === selectedAttribute )
		?.enum;

	return (
		<AppModal
			overflow="visible"
			shouldCloseOnEsc={ ! dropdownVisible }
			shouldCloseOnClickOutside={ ! dropdownVisible }
			onRequestClose={ onRequestClose }
			className="gla-attribute-mapping__rule-modal"
			title={
				rule
					? __( 'Manage attribute rule', ' google-listings-and-ads' )
					: __( 'Create attribute rule', ' google-listings-and-ads' )
			}
			buttons={ [
				<AppButton key="cancel" isLink onClick={ onRequestClose }>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
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
			<Subsection>
				<Subsection.Title>
					{ __( 'Target attribute', 'google-listings-and-ads' ) }
				</Subsection.Title>
				<Subsection.Subtitle className="gla_attribute_mapping_helper-text">
					{ __(
						'Select a Google attribute that you want to manage',
						'google-listings-and-ads'
					) }
				</Subsection.Subtitle>
				<AppSelectControl
					onChange={ setSelectedAttribute }
					options={ attributes.map( ( attribute ) => {
						return {
							value: attribute.id,
							label: attribute.label,
						};
					} ) }
				/>
			</Subsection>

			{ sources.length > 0 && (
				<>
					<Subsection>
						<Subsection.Title>
							{ isEnum
								? __(
										'Select default value',
										'google-listings-and-ads'
								  )
								: __(
										'Assign value',
										'google-listings-and-ads'
								  ) }
						</Subsection.Title>

						{ isEnum ? (
							<AttributeMappingFieldSourcesControl
								sources={ sources }
							/>
						) : (
							<AttributeMappingSourceTypeSelector
								sources={ sources }
							/>
						) }
					</Subsection>
					<Subsection>
						<Subsection.Title>
							{ __( 'Categories', 'google-listings-and-ads' ) }
						</Subsection.Title>
						<AttributeMappingCategoryControl
							onCategorySelectorOpen={ setDropdownVisible }
						/>
					</Subsection>
				</>
			) }
		</AppModal>
	);
};

export default AttributeMappingRuleModal;
