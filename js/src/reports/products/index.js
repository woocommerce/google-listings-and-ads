/**
 * Internal dependencies
 */
import TabNav from '../../tab-nav';
import SubNav from '../sub-nav';

const ProductsReport = () => {
	return (
		<div>
			<TabNav initialName="reports" />
			<SubNav initialName="products" />
		</div>
	);
};

export default ProductsReport;
