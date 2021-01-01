/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link, Form } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */

import HelpPopover from '../../../../components/help-popover';
import Section from '../../../../wcdl/section';
import VerticalGapLayout from '../components/vertical-gap-layout';
import './index.scss';

const initialValues = {
	check1: false,
	check2: false,
	check3: false,
	check4: false,
	check5: false,
	aaa: {
		bbb: {
			ccc: 123,
		},
	},
};

const PreLaunchChecklist = () => {
	// TODO: Add tooltips for each checkboxes.
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
							{ /* TODO: Link to read more on Google Merchant requirements. */ }
							<Link
								type="external"
								href="https://www.google.com/"
								target="_blank"
							>
								{ __(
									'Read Google Merchant requirements',
									'google-listings-and-ads'
								) }
							</Link>
						</p>
					</div>
				}
			>
				<Section.Card>
					<Section.Card.Body>
						<Form
							initialValues={ initialValues }
							validate={ () => ( {} ) }
							onSubmitCallback={ () => {} }
						>
							{ ( { getInputProps } ) => (
								<VerticalGapLayout>
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
													<HelpPopover id="check1">
														{ __(
															'Ensure your store website and products are online and accessible to your customers.',
															'google-listings-and-ads'
														) }
													</HelpPopover>
												</span>
											}
											{ ...getInputProps( 'check1' ) }
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
												<HelpPopover id="check2">
													<p>
														{ __(
															'Ensure customers are able to successfully add items to the cart and fully complete the checkout process.',
															'google-listings-and-ads'
														) }
													</p>
													<p>
														{ /* TODO: link URL. */ }
														{ createInterpolateElement(
															__(
																'Payment and transaction processing, as well as collection of any sensitive and financial personal information from the user, must be conducted over a secure processing server (SSL-protected, with a valid SSL certificate - https://). <link>Read more</link>',
																'google-listings-and-ads'
															),
															{
																link: (
																	<Link
																		type="external"
																		href="https://www.google.com/retail/solutions/merchant-center/"
																		target="_blank"
																	/>
																),
															}
														) }
													</p>
												</HelpPopover>
											</span>
										}
										{ ...getInputProps( 'check2' ) }
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
												<HelpPopover id="check3">
													{ __(
														'Ensure customers have at least one valid payment method, such as credit card, direct bank transfer, or cash on delivery.',
														'google-listings-and-ads'
													) }
												</HelpPopover>
											</span>
										}
										{ ...getInputProps( 'check3' ) }
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
												<HelpPopover id="check4">
													{ __(
														'Your site must provide a clear and conspicuous return policy and billing terms to customers.',
														'google-listings-and-ads'
													) }
												</HelpPopover>
											</span>
										}
										{ ...getInputProps( 'check4' ) }
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
												<HelpPopover id="check5">
													{ __(
														'Your website must display sufficient and accurate contact information to your customers, including a telephone number and/or email.',
														'google-listings-and-ads'
													) }
												</HelpPopover>
											</span>
										}
										{ ...getInputProps( 'check5' ) }
									/>
								</VerticalGapLayout>
							) }
						</Form>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default PreLaunchChecklist;
