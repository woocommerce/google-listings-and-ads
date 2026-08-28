/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Display a centered spinner.
 */
const AppSpinner = () => {
	return (
		<div
			aria-label={ __( 'Loading…', 'google-listings-and-ads' ) }
			className="app-spinner"
			role="status"
		>
			<Spinner />
		</div>
	);
};

export default AppSpinner;
