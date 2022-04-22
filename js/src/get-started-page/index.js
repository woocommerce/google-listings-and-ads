/**
 * Internal dependencies
 */
import './index.scss';
import UnsupportedNotices from './unsupported-notices';
import BenefitsCard from './benefits-card';
import GetStartedWithVideoCard from './get-started-with-video-card';

const GetStartedPage = () => {
	return (
		<div className="woocommerce-marketing-google-get-started-page">
			<UnsupportedNotices />
			<GetStartedWithVideoCard />
			<BenefitsCard />
		</div>
	);
};

export default GetStartedPage;
