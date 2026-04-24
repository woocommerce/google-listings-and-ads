/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';

/**
 * Placeholder for the "Add market" CTA on the Markets dashboard.
 *
 * The button is wired with a no-op handler for now. The follow-up task
 * will introduce `AddMarketModal` and replace the handler.
 */
const AddMarket = () => {
	return (
		<AppButton variant="primary" onClick={ () => {} }>
			{ __( 'Add market', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default AddMarket;
