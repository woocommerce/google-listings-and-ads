/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import recordColumnToggleEvent from './recordColumnToggleEvent';
import './index.scss';

const AppTableCard = ( props ) => {
	const { trackEventReportId, onColumnsChange = () => {}, ...rest } = props;

	const handleColumnsChange = ( shown, toggled ) => {
		if ( trackEventReportId ) {
			recordColumnToggleEvent( trackEventReportId, shown, toggled );
		}

		onColumnsChange( shown, toggled );
	};

	return (
		<div className="app-table-card">
			<TableCard onColumnsChange={ handleColumnsChange } { ...rest } />
		</div>
	);
};

export default AppTableCard;
