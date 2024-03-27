/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getProductFeedUrl } from '.~/utils/urls';
import { glaData } from '.~/constants';
import GuidePageContent, {
	ContentLink,
} from '.~/components/guide-page-content';

const SetupSuccess = () => {
	return (
		<GuidePageContent
			title={ __(
				'You’ve successfully set up Google Listings & Ads! 🎉',
				'google-listings-and-ads'
			) }
		>
			<p>
				{ __(
					'Your products are being synced and reviewed. Google reviews product listings in 3-5 days.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ glaData.adsSetupComplete
					? __(
							'No ads will launch yet and you won’t be charged until Google approves your listings. Updates are available in your WooCommerce dashboard.',
							'google-listings-and-ads'
					  )
					: createInterpolateElement(
							__(
								'<productFeedLink>Manage and edit your product feed in WooCommerce.</productFeedLink> We will also notify you of any product feed issues to ensure your products get approved and perform well on Google.',
								'google-listings-and-ads'
							),
							{
								productFeedLink: (
									<ContentLink
										href={ getProductFeedUrl() }
										context="product-feed"
									/>
								),
							}
					  ) }
			</p>
		</GuidePageContent>
	);
};

export default SetupSuccess;
