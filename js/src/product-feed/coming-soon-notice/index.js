/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, Icon } from '@wordpress/components';
import { external as externalIcon } from '@wordpress/icons';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import './index.scss';

const ExternalIcon = () => (
	<Icon
		className="gla-coming-soon-notice__icon"
		icon={ externalIcon }
		size={ 18 }
	/>
);

const ComingSoonNotice = () => {
	return (
		<Notice
			className="gla-coming-soon-notice"
			status="warning"
			isDismissible={ false }
		>
			{ createInterpolateElement(
				__(
					'Your products will be listed in the Product Feed section soon! We’re working on improvements to this plugin. Our estimated full-featured release is in June 2021. In the mean time, manage your synced product feed directly in <googleMerchantCenterLink>Google Merchant Center</googleMerchantCenterLink>. Optionally, <feedbackLink>give us some feedback</feedbackLink>.',
					'google-listings-and-ads'
				),
				{
					feedbackLink: (
						<AppDocumentationLink
							className="gla-coming-soon-notice__link"
							href="https://forms.gle/aA9BpfEwceAe3eBA9"
							eventName="get_coming_soon_link_click"
							context="coming-soon"
							linkId="feedback-form"
						/>
					),
					googleMerchantCenterLink: (
						<AppDocumentationLink
							className="gla-coming-soon-notice__link"
							href="https://merchants.google.com/mc/"
							eventName="get_coming_soon_link_click"
							context="coming-soon"
							linkId="google-merchant-center"
						/>
					),
				}
			) }
			<ExternalIcon />
		</Notice>
	);
};

export default ComingSoonNotice;
