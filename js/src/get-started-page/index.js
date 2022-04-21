/**
 * Internal dependencies
 */
import './index.scss';
import UnsupportedNotices from './unsupported-notices';
import Faqs from './faqs';
import GetStartedCard from './get-started-card';
import GetStartedWithVideoCard from './get-started-with-video-card';
import FeaturesCard from './features-card';

const GetStartedPage = () => {
	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<UnsupportedNotices />
			<GetStartedWithVideoCard />
			<GetStartedCard />
			<FeaturesCard />
			<Faqs></Faqs>
		</div>
	);
};

export default GetStartedPage;
