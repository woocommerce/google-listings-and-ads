/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link, Form } from '@woocommerce/components';
import { useState, createInterpolateElement } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import './index.scss';
import VerticalGapLayout from '../components/vertical-gap-layout';

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
		<Section
			title={ __( 'Pre-Launch Checklist', 'google-listings-and-ads' ) }
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
								<CheckboxControl
									label={ __(
										'My store website is live.',
										'google-listings-and-ads'
									) }
									{ ...getInputProps( 'check1' ) }
								/>
								<CheckboxControl
									label={ __(
										'I have a complete and secure checkout process.',
										'google-listings-and-ads'
									) }
									{ ...getInputProps( 'check2' ) }
								/>
								<CheckboxControl
									label={ __(
										'My payment methods are visible on my website.',
										'google-listings-and-ads'
									) }
									{ ...getInputProps( 'check3' ) }
								/>
								<CheckboxControl
									label={ __(
										'My refund policy and terms of service are visible on my online store.',
										'google-listings-and-ads'
									) }
									{ ...getInputProps( 'check4' ) }
								/>
								<CheckboxControl
									label={ __(
										'My store’s phone number, email and/or address are visible on my website.',
										'google-listings-and-ads'
									) }
									{ ...getInputProps( 'check5' ) }
								/>
							</VerticalGapLayout>
						) }
					</Form>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default PreLaunchChecklist;
