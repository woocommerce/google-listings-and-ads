/**
 * Internal dependencies
 */
import './index.scss';
import BenefitsCard from './benefits-card';
import CaseStudiesCard from './case-studies-card';
import FeaturesCard from './features-card';
import GetStartedWithVideoCard from './get-started-with-video-card';
import UnsupportedNotices from './unsupported-notices';

const GetStartedPage = () => {
	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<UnsupportedNotices />
			<GetStartedWithVideoCard />
			<BenefitsCard />
			<FeaturesCard />
			<CaseStudiesCard />
		</div>
	);
};

export default GetStartedPage;
