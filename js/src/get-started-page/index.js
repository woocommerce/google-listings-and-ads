/**
 * Internal dependencies
 */
import './index.scss';
import BenefitsCard from './benefits-card';
import CustomerQuotesCard from './customer-quotes-card';
import Faqs from './faqs';
import FeaturesCard from './features-card';
import GetStartedCard from './get-started-card';
import GetStartedWithHeroCard from './get-started-with-hero-card';
import UnsupportedNotices from './unsupported-notices';

const GetStartedPage = () => {
	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<UnsupportedNotices />
			<GetStartedWithHeroCard />
			<BenefitsCard />
			<FeaturesCard />
			<CustomerQuotesCard />
			<GetStartedCard />
			<Faqs />
		</div>
	);
};

export default GetStartedPage;
