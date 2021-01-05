/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

const EditRateButton = () => {
	return (
		<Button className="gla-edit-rate-button" isTertiary>
			{ __( 'Edit', 'google-listings-and-ads' ) }
		</Button>
	);
};

export default EditRateButton;
