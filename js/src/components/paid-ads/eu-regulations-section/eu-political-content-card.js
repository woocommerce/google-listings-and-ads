/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import AppDocumentationLink from '~/components/app-documentation-link';

/**
 * Displays a checkbox to confirm whether political content is advertised.
 * @fires gla_documentation_link_click with `{ context: 'setup-mc', link_id: 'eu-political-content', href: 'https://support.google.com/adspolicy/answer/6014595?hl=en' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'eu-political-content', href: 'https://support.google.com/adspolicy/answer/6014595?hl=en' }`
 * @fires gla_documentation_link_click with `{ context: 'create-ads', link_id: 'eu-political-content', href: 'https://support.google.com/adspolicy/answer/6014595?hl=en' }`
 */
const EuPoliticalContentCard = ( { context } ) => {
	const { getInputProps } = useAdaptiveFormContext();
	const inputProps = getInputProps( 'contains_eu_political_advertising' );

	return (
		<Section.Card className="gla-eu-political-content-card">
			<Section.Card.Body>
				<Section.Card.Title>
					{ __( 'EU political content', 'google-listings-and-ads' ) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					<CheckboxControl
						label={ createInterpolateElement(
							__(
								'I confirm I don’t advertise political content as defined by Google’s <link>EU political content policy</link>.',
								'google-listings-and-ads'
							),
							{
								link: (
									<AppDocumentationLink
										href="https://support.google.com/adspolicy/answer/6014595?hl=en"
										linkId="eu-political-content"
										context={ context }
									/>
								),
							}
						) }
						{ ...inputProps }
						help={ __(
							'Required if you include EU countries in your selected locations.',
							'google-listings-and-ads'
						) }
					/>
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default EuPoliticalContentCard;
