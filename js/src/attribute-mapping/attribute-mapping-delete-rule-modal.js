/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import { useAppDispatch } from '.~/data';
import { recordGlaEvent } from '.~/utils/tracks';

/**
 * Deletes the rule successfully
 *
 * @event gla_attribute_mapping_delete_rule
 * @property {string} context Indicates where this event happened
 */

/**
 * Clicks on delete rule button
 *
 * @event gla_attribute_mapping_delete_rule_click
 * @property {string} context Indicates where this event happened
 */

/**
 * Renders a Modal to confirm Attribute Mapping Rule deletion
 *
 * @param {Object} props Component props
 * @param {Function} props.onRequestClose Callback function when the modal is closed
 * @param {Object} props.rule The rule to be deleted
 * @fires gla_attribute_mapping_delete_rule When the rule is successfully deleted with `{ context: 'attribute-mapping-delete-rule-modal'}`
 * @fires gla_attribute_mapping_delete_rule_click When user clicks on delete rule button with `{ context: 'attribute-mapping-delete-rule-modal' }`
 */
const AttributeMappingDeleteRuleModal = ( { onRequestClose = noop, rule } ) => {
	const [ deleting, setDeleting ] = useState( false );
	const { deleteMappingRule } = useAppDispatch();

	const onDelete = async () => {
		setDeleting( true );

		try {
			await deleteMappingRule( rule );
			recordGlaEvent( 'gla_attribute_mapping_delete_rule', {
				context: 'attribute-mapping-delete-rule-modal',
			} );
			onRequestClose( 'confirm' );
		} catch ( error ) {
			setDeleting( false );
		}
	};

	const handleClose = () => {
		if ( deleting ) {
			return;
		}
		onRequestClose( 'dismiss' );
	};

	return (
		<AppModal
			onRequestClose={ handleClose }
			title={ __( 'Delete attribute rule?', 'google-listings-and-ads' ) }
			buttons={ [
				<AppButton
					disabled={ deleting }
					key="cancel"
					isLink
					onClick={ handleClose }
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					disabled={ deleting }
					key="delete-rule"
					isPrimary
					text={
						deleting
							? __( 'Deleting…', 'google-listings-and-ads' )
							: __(
									'Delete attribute rule',
									'google-listings-and-ads'
							  )
					}
					eventName="gla_attribute_mapping_delete_rule_click"
					eventProps={ {
						context: 'attribute-mapping-delete-rule-modal',
					} }
					onClick={ onDelete }
				/>,
			] }
		>
			<div>
				<p>
					{ __(
						'Deleting a rule does’t affect any data that has already been submitted to Google.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ __(
						'Product data is re-submitted to Google every 30 days to ensure that the information in your product listings are up-to-date.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ __(
						'To ensure your products continue to be approved and promoted by Google, make sure that your product fields include all the required information.',
						'google-listings-and-ads'
					) }
				</p>
			</div>
		</AppModal>
	);
};

export default AttributeMappingDeleteRuleModal;
