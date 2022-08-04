/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */

import HelpPopover from '.~/components/help-popover';
import AppDocumentationLink from '.~/components/app-documentation-link';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import './index.scss';

/*
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-checklist', link_id: 'checklist-requirements', href: 'https://support.google.com/merchants/answer/6363310' }`
 */
const PreLaunchChecklist = ( props ) => {
	const { formProps } = props;
	const { getInputProps } = formProps;

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
							<div className="gla-pre-launch-checklist__checkbox_popover">
								<CheckboxControl
									label={
										<span className="gla-pre-launch-checklist__checkbox_popover">
											<span className="checkbox-label">
												{ __(
													'My store website is live.',
													'google-listings-and-ads'
												) }
											</span>
											<HelpPopover id="website_live">
												{ createInterpolateElement(
													__(
														'Ensure your store website and products are online and accessible to your customers. <link>Read more</link>',
														'google-listings-and-ads'
													),
													{
														link: (
															<AppDocumentationLink
																context="setup-mc-checklist"
																linkId="check-website-is-live"
																href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#store-is-live"
															/>
														),
													}
												) }
											</HelpPopover>
										</span>
									}
									{ ...getInputProps( 'website_live' ) }
								/>
							</div>
							<CheckboxControl
								label={
									<span className="gla-pre-launch-checklist__checkbox_popover">
										<span className="checkbox-label">
											{ __(
												'I have a complete and secure checkout process.',
												'google-listings-and-ads'
											) }
										</span>
										<HelpPopover id="checkout_process_secure">
											<p>
												{ __(
													'Ensure customers are able to successfully add items to the cart and fully complete the checkout process.',
													'google-listings-and-ads'
												) }
											</p>
											<p>
												{ createInterpolateElement(
													__(
														'Payment and transaction processing, as well as collection of any sensitive and financial personal information from the user, must be conducted over a secure processing server (SSL-protected, with a valid SSL certificate - https://). <link>Read more</link>',
														'google-listings-and-ads'
													),
													{
														link: (
															<AppDocumentationLink
																context="setup-mc-checklist"
																linkId="check-checkout-process"
																href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#complete-checkout"
															/>
														),
													}
												) }
											</p>
										</HelpPopover>
									</span>
								}
								{ ...getInputProps(
									'checkout_process_secure'
								) }
							/>
							<CheckboxControl
								label={
									<span className="gla-pre-launch-checklist__checkbox_popover">
										<span className="checkbox-label">
											{ __(
												'My payment methods are visible on my website.',
												'google-listings-and-ads'
											) }
										</span>
										<HelpPopover id="payment_methods_visible">
											{ createInterpolateElement(
												__(
													'Ensure customers have at least one valid payment method, such as credit card, direct bank transfer, or cash on delivery. <link>Read more</link>',
													'google-listings-and-ads'
												),
												{
													link: (
														<AppDocumentationLink
															context="setup-mc-checklist"
															linkId="check-payment-methods"
															href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#payment-methods"
														/>
													),
												}
											) }
										</HelpPopover>
									</span>
								}
								{ ...getInputProps(
									'payment_methods_visible'
								) }
							/>
							<CheckboxControl
								label={
									<span className="gla-pre-launch-checklist__checkbox_popover">
										<span className="checkbox-label">
											{ __(
												'My refund policy and terms of service are visible on my online store.',
												'google-listings-and-ads'
											) }
										</span>
										<HelpPopover id="refund_tos_visible">
											{ createInterpolateElement(
												__(
													'Your site must provide a clear and conspicuous return policy and billing terms to customers. <link>Read more</link>',
													'google-listings-and-ads'
												),
												{
													link: (
														<AppDocumentationLink
															context="setup-mc-checklist"
															linkId="check-refund-policy"
															href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#refund-and-terms"
														/>
													),
												}
											) }
										</HelpPopover>
									</span>
								}
								{ ...getInputProps( 'refund_tos_visible' ) }
							/>
							<CheckboxControl
								label={
									<span className="gla-pre-launch-checklist__checkbox_popover">
										<span className="checkbox-label">
											{ __(
												'My store’s phone number, email and/or address are visible on my website.',
												'google-listings-and-ads'
											) }
										</span>
										<HelpPopover id="contact_info_visible">
											{ createInterpolateElement(
												__(
													'Your website must display sufficient and accurate contact information to your customers, including a telephone number and/or email. <link>Read more</link>',
													'google-listings-and-ads'
												),
												{
													link: (
														<AppDocumentationLink
															context="setup-mc-checklist"
															linkId="check-phone-numbers"
															href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#contact-info"
														/>
													),
												}
											) }
										</HelpPopover>
									</span>
								}
								{ ...getInputProps( 'contact_info_visible' ) }
							/>
						</VerticalGapLayout>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default PreLaunchChecklist;
