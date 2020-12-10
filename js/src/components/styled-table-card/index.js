/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import recordColumnToggleEvent from './recordColumnToggleEvent';
import './index.scss';

const StyledTableCard = ( props ) => {
	const { trackEventReportId, onColumnsChange = () => {}, ...rest } = props;

	const handleColumnsChange = ( shown, toggled ) => {
		if ( trackEventReportId ) {
			recordColumnToggleEvent( trackEventReportId, shown, toggled );
		}

		onColumnsChange( shown, toggled );
	};

	return (
		<div className="gla-styled-table-card">
			<TableCard onColumnsChange={ handleColumnsChange } { ...rest } />
		</div>
	);
};

export default StyledTableCard;
