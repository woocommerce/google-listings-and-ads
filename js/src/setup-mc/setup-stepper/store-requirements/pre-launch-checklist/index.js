/**
 * External dependencies
 */
 import { __ } from '@wordpress/i18n';
 import { createInterpolateElement } from '@wordpress/element';
 import { CheckboxControl } from '@wordpress/components';
 import { Button } from '@wordpress/components';
 import { Panel, PanelBody, PanelRow } from '@wordpress/components';

 /**
  * Internal dependencies
  */
 import { useAppDispatch } from '.~/data';
 import AppSpinner from '.~/components/app-spinner';
 import AppDocumentationLink from '.~/components/app-documentation-link';
 import Section from '.~/wcdl/section';
 import VerticalGapLayout from '.~/components/vertical-gap-layout';
 import './index.scss';
 import usePolicyCheck from '.~/hooks/usePolicyCheck';

 /*
  * @fires gla_documentation_link_click with `{ context: 'setup-mc-checklist', link_id: 'checklist-requirements', href: 'https://support.google.com/merchants/answer/6363310' }`
  */
 const PreLaunchChecklist = ( props ) => {
		const { saveSettings } = useAppDispatch();
		const { formProps } = props;

		const { getInputProps, setValue, values } = formProps;

		const getPanelToggleHandler = ( trackName, id, context ) => (
			isOpened
		) => {
			recordEvent( trackName, {
				id,
				action: isOpened ? 'expand' : 'collapse',
				context,
			} );
		};

		if ( ! values[ 'checked' ] ) {
			const { data } = usePolicyCheck();
			if ( ! data ) {
				return <AppSpinner />;
			}
			var website_live =
				data.allowed_countries &&
				! data.robots_restriction &&
				! data.page_not_found_error &&
				! data.page_redirects;
			if ( website_live != values[ 'website_live' ] ) {
				setValue( 'website_live', website_live );
			}

			if ( data.store_ssl != values[ 'checkout_process_secure' ] ) {
				setValue( 'checkout_process_secure', data.store_ssl );
			}

			if ( data.refund_returns != values[ 'refund_tos_visible' ] ) {
				setValue( 'refund_tos_visible', data.refund_returns );
			}

			if (
				data.payment_gateways != values[ 'payment_methods_visible' ]
			) {
				setValue( 'payment_methods_visible', data.payment_gateways );
			}

			setValue( 'checked', true );
		}

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
								{ ! values[ 'website_live' ] && (
									<div id="checkbox">
										<CheckboxControl
											{ ...getInputProps(
												'website_live'
											) }
										></CheckboxControl>
										<div id="panel">
											<Panel>
												<PanelBody
													key={
														'why-do-i-need-a-wp-account'
													}
													title={ __(
														'My store is live and accessible to all users',
														'google-listings-and-ads'
													) }
													initialOpen={ true }
													onToggle={ getPanelToggleHandler(
														'gla_faq',
														'why-do-i-need-a-wp-account',
														'setup-mc-accounts'
													) }
												>
													<PanelRow>
														{ __(
															'We use a WordPress.com account to connect your site to the WooCommerce and Google servers. It ensures that requests (e.g. product feed, clicks, sales, etc) from your site are securely and correctly attributed to your store. It enables a connection to your self-hosted site, and provides a common authentication interface across disparate server configurations and architectures.',
															'google-listings-and-ads'
														) }
													</PanelRow>
													<p>
														{ createInterpolateElement(
															__(
																'<link>Learn more about common landing page issues and how to fix them  </link>',
																'google-listings-and-ads'
															),
															{
																link: (
																	<AppDocumentationLink
																		context="setup-mc-checklist"
																		linkId="check-checkout-process"
																		type="external"
																		href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#store-is-live"
																	/>
																),
															}
														) }
													</p>
													<Button
														isPrimary
														disabled={
															values[
																'website_live'
															]
														}
														onClick={ () => {
															setValue(
																'website_live',
																true
															);
															saveSettings(
																values[
																	website_live
																],
																true
															);
														} }
													>
														{ __(
															'Confirm',
															'google-listings-and-ads'
														) }
													</Button>
												</PanelBody>
											</Panel>
										</div>
									</div>
								) }
								{ values[ 'website_live' ] && (
									<CheckboxControl
										label={
											<span className="gla-pre-launch-checklist__checkbox_popover">
												<span className="checkbox-label">
													{ __(
														'My store is live and accessible to all users.',
														'google-listings-and-ads'
													) }
												</span>
											</span>
										}
										{ ...getInputProps( 'website_live' ) }
									/>
								) }
								{ ! values[ 'payment_methods_visible' ] && (
									<div id="checkbox">
										<CheckboxControl
											{ ...getInputProps(
												'payment_methods_visible'
											) }
										></CheckboxControl>
										<div id="panel">
											<Panel>
												<PanelBody
													key={
														'why-do-i-need-a-wp-account'
													}
													title={ __(
														'I have a complete checkout process.',
														'google-listings-and-ads'
													) }
													initialOpen={ true }
													onToggle={ getPanelToggleHandler(
														'gla_faq',
														'why-do-i-need-a-wp-account',
														'setup-mc-accounts'
													) }
												>
													<PanelRow>
														{ __(
															'Ensure that all customers are able to complete the full checkout process on your site with an eligible payment method. Include a confirmation of the purchase after completion of the checkout process. ',
															'google-listings-and-ads'
														) }
													</PanelRow>
													<p>
														{ createInterpolateElement(
															__(
																"<link>Learn more about Google's checkout requirements & best practices</link>",
																'google-listings-and-ads'
															),
															{
																link: (
																	<AppDocumentationLink
																		context="setup-mc-checklist"
																		linkId="check-checkout-process"
																		type="external"
																		href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#complete-checkout"
																	/>
																),
															}
														) }
													</p>
													<Button
														isPrimary
														disabled={
															values[
																'payment_methods_visible'
															]
														}
														onClick={ () => {
															setValue(
																'payment_methods_visible',
																true
															);
															saveSettings(
																values[
																	'payment_methods_visible'
																],
																true
															);
														} }
													>
														{ __(
															'Confirm',
															'google-listings-and-ads'
														) }
													</Button>
												</PanelBody>
											</Panel>
										</div>
									</div>
								) }

								{ values[ 'payment_methods_visible' ] && (
									<CheckboxControl
										label={
											<span className="gla-pre-launch-checklist__checkbox_popover">
												<span className="checkbox-label">
													{ __(
														'I have a complete checkout process.',
														'google-listings-and-ads'
													) }
												</span>
											</span>
										}
										{ ...getInputProps(
											'payment_methods_visible'
										) }
									/>
								) }
								{ ! values[ 'checkout_process_secure' ] && (
									<div id="checkbox">
										<CheckboxControl
											{ ...getInputProps(
												'checkout_process_secure'
											) }
										></CheckboxControl>
										<div id="panel">
											<Panel>
												<PanelBody
													key={
														'why-do-i-need-a-wp-account'
													}
													title={ __(
														'Confirm you have a secure checkout process.',
														'google-listings-and-ads'
													) }
													initialOpen={ true }
													onToggle={ getPanelToggleHandler(
														'gla_faq',
														'why-do-i-need-a-wp-account',
														'setup-mc-accounts'
													) }
												>
													<PanelRow>
														{ __(
															"Update your website to ensure that every webpage that collects a customer's personal information is processed through a secure SSL server. Any page on your website that collects any personal information from the user needs to be SSL protected.",
															'google-listings-and-ads'
														) }
													</PanelRow>

													<PanelRow>
														{ __(
															"Use a secure server: Make sure to use a secure processing server when processing customer's personal information (SSL-protected, with a valid SSL certificate). With SSL, your webpage URL will appear with https:// instead of http://,",
															'google-listings-and-ads'
														) }
													</PanelRow>
													<p>
														{ createInterpolateElement(
															__(
																'<link>Learn to set up SSL on your website</link>',
																'google-listings-and-ads'
															),
															{
																link: (
																	<AppDocumentationLink
																		context="setup-mc-checklist"
																		linkId="check-checkout-process"
																		type="external"
																		href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#payment-methods"
																	/>
																),
															}
														) }
													</p>
													<Button
														isPrimary
														disabled={
															values[
																'checkout_process_secure'
															]
														}
														onClick={ () => {
															setValue(
																'checkout_process_secure',
																true
															);
															saveSettings(
																values[
																	'checkout_process_secure'
																],
																true
															);
														} }
													>
														{ __(
															'Confirm',
															'google-listings-and-ads'
														) }
													</Button>
												</PanelBody>
											</Panel>
										</div>
									</div>
								) }
								{ values[ 'checkout_process_secure' ] && (
									<CheckboxControl
										label={
											<span className="gla-pre-launch-checklist__checkbox_popover">
												<span className="checkbox-label">
													{ __(
														'Confirm you have a secure checkout process.',
														'google-listings-and-ads'
													) }
												</span>
											</span>
										}
										{ ...getInputProps(
											'checkout_process_secure'
										) }
									/>
								) }

								{ ! values[ 'refund_tos_visible' ] && (
									<div id="checkbox">
										<CheckboxControl
											{ ...getInputProps(
												'refund_tos_visible'
											) }
										></CheckboxControl>
										<div id="panel">
											<Panel>
												<PanelBody
													key={
														'why-do-i-need-a-wp-account'
													}
													title={ __(
														'Confirm a refund policy and terms of service are visible on your online store.',
														'google-listings-and-ads'
													) }
													initialOpen={ true }
													onToggle={ getPanelToggleHandler(
														'gla_faq',
														'why-do-i-need-a-wp-account',
														'setup-mc-accounts'
													) }
												>
													<PanelRow>
														{ __(
															"Show a clear return and refund policy on your website. Incluse return process, refund process, and customer requirements (return window, product condition and reason for return). If you don't accept returns or refunds, clearly start that on your website. ",
															'google-listings-and-ads'
														) }
													</PanelRow>
													<p>
														{ createInterpolateElement(
															__(
																"<link>Learn more about Google's refund policy requirements </link>",
																'google-listings-and-ads'
															),
															{
																link: (
																	<AppDocumentationLink
																		context="setup-mc-checklist"
																		linkId="check-checkout-process"
																		type="external"
																		href="https://woocommerce.com/document/google-listings-and-ads/compliance-policy/#refund-and-terms"
																	/>
																),
															}
														) }
													</p>
													<Button
														isPrimary
														disabled={
															values[
																'refund_tos_visible'
															]
														}
														onClick={ () => {
															setValue(
																'refund_tos_visible',
																true
															);
															saveSettings(
																values
															);
														} }
													>
														{ __(
															'Confirm',
															'google-listings-and-ads'
														) }
													</Button>
												</PanelBody>
											</Panel>
										</div>
									</div>
								) }

								{ values[ 'refund_tos_visible' ] && (
									<CheckboxControl
										label={
											<span className="gla-pre-launch-checklist__checkbox_popover">
												<span className="checkbox-label">
													{ __(
														'Confirm a refund policy and terms of service are visible on your online store.',
														'google-listings-and-ads'
													) }
												</span>
											</span>
										}
										{ ...getInputProps(
											'refund_tos_visible'
										) }
									/>
								) }

								{ ! values[ 'contact_info_visible' ] && (
									<div id="checkbox">
										<CheckboxControl
											{ ...getInputProps(
												'contact_info_visible'
											) }
										></CheckboxControl>
										<div id="panel">
											<Panel>
												<PanelBody
													title={ __(
														"Confirm your store's phone number, email and/or address are visible on your website",
														'google-listings-and-ads'
													) }
													initialOpen={ true }
													onToggle={ getPanelToggleHandler(
														'gla_faq',
														'why-do-i-need-a-wp-account',
														'setup-mc-accounts'
													) }
												>
													<PanelRow>
														{ __(
															'Allow your customers to contact you for product inquiries by including contact information on your website (i,e, contact us form, business profile link, social media, email or phone number.',
															'google-listings-and-ads'
														) }
													</PanelRow>
													<p />
													<Button
														isPrimary
														disabled={
															values[
																'contact_info_visible'
															]
														}
														onClick={ () => {
															setValue(
																'contact_info_visible',
																true
															);
															saveSettings(
																values[
																	contact_info_visible
																],
																true
															);
														} }
													>
														{ __(
															'Confirm',
															'google-listings-and-ads'
														) }
													</Button>
												</PanelBody>
											</Panel>
										</div>
									</div>
								) }
								{ values[ 'contact_info_visible' ] && (
									<CheckboxControl
										label={
											<span className="gla-pre-launch-checklist__checkbox_popover">
												<span className="checkbox-label">
													{ __(
														"Confirm your store's phone number, email and/or address are visible on your website.",
														'google-listings-and-ads'
													) }
												</span>
											</span>
										}
										{ ...getInputProps(
											'contact_info_visible'
										) }
									/>
								) }
							</VerticalGapLayout>
						</Section.Card.Body>
					</Section.Card>
				</Section>
			</div>
		);
 };

 export default PreLaunchChecklist;
