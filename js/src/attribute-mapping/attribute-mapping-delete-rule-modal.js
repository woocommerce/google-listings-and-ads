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

const AttributeMappingDeleteRuleModal = ( { onRequestClose = noop, rule } ) => {
	const [ deleting, setDeleting ] = useState( false );
	const { deleteMappingRule } = useAppDispatch();

	const onDelete = async () => {
		setDeleting( true );

		try {
			await deleteMappingRule( rule );
			onRequestClose();
		} catch ( error ) {
			setDeleting( false );
		}
	};

	return (
		<AppModal
			title={ __( 'Delete attribute rule?', ' google-listings-and-ads' ) }
			buttons={ [
				<AppButton key="cancel" isLink onClick={ onRequestClose }>
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
					eventName="gla_attribute_mapping_delete_rule"
					eventProps={ {
						context: 'attribute-mapping-rule-modal',
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
