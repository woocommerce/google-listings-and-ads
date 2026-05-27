/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import MarketsDashboard from './components/markets-dashboard';
import './index.scss';

const Markets = () => {
	return (
		<div className="gla-markets">
			<MainTabNav />
			<MarketsDashboard />
		</div>
	);
};

export default Markets;
