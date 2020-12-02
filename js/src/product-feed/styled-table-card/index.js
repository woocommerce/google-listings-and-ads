/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './index.scss';

const StyledTableCard = ( props ) => {
	return (
		<div className="gla-styled-table-card">
			<TableCard { ...props } />
		</div>
	);
};

export default StyledTableCard;
