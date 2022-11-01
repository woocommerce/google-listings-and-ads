/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { isEqual, noop } from 'lodash';

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
import AppSpinner from '.~/components/app-spinner';
import { useAppDispatch } from '.~/data';
import { CATEGORY_CONDITION_SELECT_TYPES } from '.~/constants';

const enumSelectorLabel = __(
	'Select default value',
	'google-listings-and-ads'
);

const attributeSelectorLabel = __(
	'Select a Google attribute that you want to manage',
	'google-listings-and-ads'
);

/**
 * Map the format received from the backend into the format needed in the SelectControl
 *
 * @param {{id: string, label: string}[]} data The array with the values from the backend
 * @return {{label: string, value: string}[]} The data formatted
 */
const mapOptions = ( data = [] ) => {
	return [
		...data.map( ( attribute ) => {
			return {
				value: attribute.id,
				label: attribute.label,
			};
		} ),
	];
};

const prepareRule = ( newRule ) => {
	return {
		...newRule,
		categories:
			newRule.category_condition_type ===
			CATEGORY_CONDITION_SELECT_TYPES.ALL
				? ''
				: newRule.categories,
	};
};

/**
 * Renders a modal showing a form for editing or creating an Attribute Mapping rule
 *
 * @param {Object} props React props
 * @param {Object} [props.rule] Optional rule to manage
 * @param {Function} [props.onRequestClose] Callback on closing the modal
 */
const AttributeMappingRuleModal = ( { rule, onRequestClose = noop } ) => {
	const [ newRule, setNewRule ] = useState(
		rule
			? { ...rule }
			: { category_condition_type: CATEGORY_CONDITION_SELECT_TYPES.ALL }
	);

	const [ saving, setSaving ] = useState( false );

	const { updateMappingRule, createMappingRule } = useAppDispatch();

	const { data: attributes } = useMappingAttributes();
	const {
		data: sources = [],
		hasFinishedResolution: sourcesHasFinishedResolution,
	} = useMappingAttributesSources( newRule.attribute );

	const isEnum =
		attributes.find( ( { id } ) => id === newRule.attribute )?.enum ||
		false;

	const sourcesOptions = mapOptions( sources );

	const attributesOptions = [
		{
			value: '',
			label: __( 'Select one attribute', 'google-listings-and-ads' ),
		},
		...mapOptions( attributes ),
	];

	const isValidRule =
		newRule.source &&
		newRule.attribute &&
		( newRule.category_condition_type ===
			CATEGORY_CONDITION_SELECT_TYPES.ALL ||
			newRule.categories?.length > 0 ) &&
		! isEqual( newRule, rule );

	const onSave = async () => {
		setSaving( true );

		try {
			if ( rule ) {
				await updateMappingRule( newRule );
			} else {
				await createMappingRule( newRule );
			}
			onRequestClose();
		} catch ( error ) {
			setSaving( false );
		}
	};

	const updateRule = ( data ) => {
		setNewRule( prepareRule( data ) );
	};

	const onSourceUpdate = ( source ) => {
		updateRule( { ...newRule, source } );
	};

	const handleClose = () => {
		if ( saving ) return;
		onRequestClose();
	};

	return (
		<AppModal
			overflow="visible"
			onRequestClose={ handleClose }
			className="gla-attribute-mapping__rule-modal"
			title={
				rule
					? __( 'Manage attribute rule', ' google-listings-and-ads' )
					: __( 'Create attribute rule', ' google-listings-and-ads' )
			}
			buttons={ [
				<AppButton
					disabled={ saving }
					key="cancel"
					isLink
					onClick={ handleClose }
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					disabled={ ! isValidRule || saving }
					key="save-rule"
					isPrimary
					text={
						saving
							? __( 'Saving…', 'google-listings-and-ads' )
							: __( 'Save rule', 'google-listings-and-ads' )
					}
					eventName="gla_attribute_mapping_save_rule"
					eventProps={ {
						context: 'attribute-mapping-rule-modal',
					} }
					onClick={ onSave }
				/>,
			] }
		>
			<Subsection>
				<Subsection.Title>
					{ __( 'Target attribute', 'google-listings-and-ads' ) }
				</Subsection.Title>
				<Subsection.Subtitle className="gla_attribute_mapping_helper-text">
					{ attributeSelectorLabel }
				</Subsection.Subtitle>
				<AppSelectControl
					value={ newRule.attribute }
					aria-label={ attributeSelectorLabel }
					onChange={ ( attribute ) => {
						updateRule( { ...newRule, attribute, source: '' } );
					} }
					options={ attributesOptions }
				/>
			</Subsection>

			{ ! sourcesHasFinishedResolution && <AppSpinner /> }

			{ sourcesOptions.length > 0 && sourcesHasFinishedResolution && (
				<>
					<Subsection>
						<Subsection.Title>
							{ isEnum
								? enumSelectorLabel
								: __(
										'Assign value',
										'google-listings-and-ads'
								  ) }
						</Subsection.Title>

						{ isEnum ? (
							<AttributeMappingFieldSourcesControl
								sources={ sourcesOptions }
								onChange={ onSourceUpdate }
								value={ newRule.source }
								aria-label={ enumSelectorLabel }
							/>
						) : (
							<AttributeMappingSourceTypeSelector
								sources={ sourcesOptions }
								onChange={ onSourceUpdate }
								value={ newRule.source }
							/>
						) }
					</Subsection>
					<Subsection>
						<Subsection.Title>
							{ __( 'Categories', 'google-listings-and-ads' ) }
						</Subsection.Title>
						<AttributeMappingCategoryControl
							selectedConditionalType={
								newRule.category_condition_type
							}
							selectedCategories={
								newRule.categories?.length
									? newRule.categories.split( ',' )
									: []
							}
							onConditionalTypeChange={ ( type ) => {
								updateRule( {
									...newRule,
									category_condition_type: type,
								} );
							} }
							onCategoriesChange={ ( categories ) => {
								updateRule( {
									...newRule,
									categories: categories.join( ',' ),
								} );
							} }
						/>
					</Subsection>
				</>
			) }
		</AppModal>
	);
};

export default AttributeMappingRuleModal;
