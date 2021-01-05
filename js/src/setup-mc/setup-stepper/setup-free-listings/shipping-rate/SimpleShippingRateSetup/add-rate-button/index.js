/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';

const AddRateButton = () => {
	// TODO: click on the button should open modal popup.

	return (
		<Button isSecondary icon={ <GridiconPlusSmall /> }>
			{ __( 'Add another rate', 'google-listings-and-ads' ) }
		</Button>
	);
};

export default AddRateButton;
