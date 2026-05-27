/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import ExperienceRatingBanner from '~/components/experience-rating-banner';
import MarketsDashboard from './components/markets-dashboard';
import './index.scss';

const Markets = () => {
	return (
		<div className="gla-markets">
			<ExperienceRatingBanner />
			<MainTabNav />
			<MarketsDashboard />
		</div>
	);
};

export default Markets;
