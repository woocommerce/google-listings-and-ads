/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import PreLaunchCheckItem from '.~/components/pre-launch-check-item';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import './index.scss';

/*
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-checklist', link_id: 'checklist-requirements', href: 'https://support.google.com/merchants/answer/6363310' }`
 */
const PreLaunchChecklist = ( props ) => {
	const { formProps } = props;

	return (
		<div className="gla-pre-launch-checklist">
			<Section
				title={ __(
					'Pre-Launch Checklist',
					'google-listings-and-ads'
				) }
				description={
					<div>
						<p>
							{ __(
								'Ensure you meet Google Merchant Center requirements by reviewing this checklist. Otherwise, your products may be disapproved or your Google Merchant Center account may be suspended by Google.',
								'google-listings-and-ads'
							) }
						</p>
						<p>
							<AppDocumentationLink
								context="setup-mc-checklist"
								linkId="checklist-requirements"
								href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy"
							>
								{ __(
									'Read Google Merchant requirements',
									'google-listings-and-ads'
								) }
							</AppDocumentationLink>
						</p>
					</div>
				}
			>
				<Section.Card>
					<Section.Card.Body>
						<VerticalGapLayout size="large">
							<PreLaunchCheckItem
								formProps={ formProps }
								fieldName="website_live"
								firstPersonTitle={ __(
									'My store is live and accessible to all users',
									'google-listings-and-ads'
								) }
								secondPersonTitle={ __(
									'Confirm your store is live and accessible to all users',
									'google-listings-and-ads'
								) }
							>
								{ __(
									'Your Merchant Center account can be suspended if your store is not functional. Ensure that your domain and product pages are not leading to an under construction webpage, or an error page that displays a status code beginning with a 4 or 5 (such as a 405 error).',
									'google-listings-and-ads'
								) }
								<AppDocumentationLink
									context="setup-mc-checklist"
									linkId="check-website-is-live"
									type="external"
									href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#store-is-live"
								>
									{ __(
										'Learn more about common landing page issues and how to fix them',
										'google-listings-and-ads'
									) }
								</AppDocumentationLink>
							</PreLaunchCheckItem>
							<PreLaunchCheckItem
								formProps={ formProps }
								fieldName="payment_methods_visible"
								firstPersonTitle={ __(
									'My store is live and accessible to all users',
									'google-listings-and-ads'
								) }
								secondPersonTitle={ __(
									'I have a complete checkout process',
									'google-listings-and-ads'
								) }
							>
								{ __(
									'Ensure that all customers are able to complete the full checkout process on your site with an eligible payment method. Include a confirmation of the purchase after completion of the checkout process. ',
									'google-listings-and-ads'
								) }
								<AppDocumentationLink
									context="setup-mc-checklist"
									linkId="check-payment-methods-visible"
									type="external"
									href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#payment-methods"
								>
									{ __(
										"Learn more about Google's checkout requirements & best practices",
										'google-listings-and-ads'
									) }
								</AppDocumentationLink>
							</PreLaunchCheckItem>
							<PreLaunchCheckItem
								formProps={ formProps }
								fieldName="checkout_process_secure"
								firstPersonTitle={ __(
									'I have a secure checkout process',
									'google-listings-and-ads'
								) }
								secondPersonTitle={ __(
									'Confirm you have a secure checkout process',
									'google-listings-and-ads'
								) }
							>
								{ __(
									"Update your website to ensure that every webpage that collects a customer's personal information is processed through a secure SSL server. Any page on your website that collects any personal information from the user needs to be SSL protected.",
									'google-listings-and-ads'
								) }
								{ __(
									"Use a secure server: Make sure to use a secure processing server when processing customer's personal information (SSL-protected, with a valid SSL certificate). With SSL, your webpage URL will appear with https:// instead of http://",
									'google-listings-and-ads'
								) }
								<AppDocumentationLink
									context="setup-mc-checklist"
									linkId="check-checkout-process-secure"
									type="external"
									href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#payment-methods"
								>
									{ __(
										'Learn to set up SSL on your website',
										'google-listings-and-ads'
									) }
								</AppDocumentationLink>
							</PreLaunchCheckItem>
							<PreLaunchCheckItem
								formProps={ formProps }
								fieldName="refund_tos_visible"
								firstPersonTitle={ __(
									'My refund policy and terms of service are visible on my online store',
									'google-listings-and-ads'
								) }
								secondPersonTitle={ __(
									'Confirm a refund policy and terms of service are visible on your online store',
									'google-listings-and-ads'
								) }
							>
								{ __(
									"Show a clear return and refund policy on your website. Incluse return process, refund process, and customer requirements (return window, product condition and reason for return). If you don't accept returns or refunds, clearly start that on your website. ",
									'google-listings-and-ads'
								) }
								<AppDocumentationLink
									context="setup-mc-checklist"
									linkId="check-refund-tos-visible"
									type="external"
									href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#refund-and-terms"
								>
									{ __(
										"Learn more about Google's refund policy requirements",
										'google-listings-and-ads'
									) }
								</AppDocumentationLink>
							</PreLaunchCheckItem>
							<PreLaunchCheckItem
								formProps={ formProps }
								fieldName="contact_info_visible"
								firstPersonTitle={ __(
									"My store's phone number, email and/or address are visible on my website",
									'google-listings-and-ads'
								) }
								secondPersonTitle={ __(
									"Confirm your store's phone number, email and/or address are visible on your website",
									'google-listings-and-ads'
								) }
							>
								{ __(
									'Allow your customers to contact you for product inquiries by including contact information on your website (i,e, contact us form, business profile link, social media, email or phone number.',
									'google-listings-and-ads'
								) }
								<AppDocumentationLink
									context="setup-mc-checklist"
									linkId="check-contact-info-visible"
									type="external"
									href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#contact-info"
								>
									{ __(
										'Lean about adding your business contact information to your website',
										'google-listings-and-ads'
									) }
								</AppDocumentationLink>
							</PreLaunchCheckItem>
						</VerticalGapLayout>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default PreLaunchChecklist;
