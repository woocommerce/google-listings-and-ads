/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Display a centered spinner.
 */
const AppSpinner = () => {
	return (
		<div className="app-spinner">
			<Spinner />
		</div>
	);
};

export default AppSpinner;
