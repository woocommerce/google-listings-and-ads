/**
 * Internal dependencies
 */
import MainPageHeader from '~/components/main-page-header';
import GtinMigrationBanner from '~/components/gtin-migration-banner';
import MarketsDashboard from './components/markets-dashboard';
import './index.scss';

const Markets = () => {
	return (
		<div className="gla-markets">
			<MainPageHeader />
			<GtinMigrationBanner />
			<MarketsDashboard />
		</div>
	);
};

export default Markets;
