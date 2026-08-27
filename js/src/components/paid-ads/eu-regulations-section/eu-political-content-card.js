/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CheckboxControl, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import AppDocumentationLink from '~/components/app-documentation-link';

/**
 * Displays a checkbox to confirm whether political content is advertised.
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'eu-political-content', href: 'https://support.google.com/adspolicy/answer/6014595' }`
 * @fires gla_documentation_link_click with `{ context: 'create-ads', link_id: 'eu-political-content', href: 'https://support.google.com/adspolicy/answer/6014595' }`
 * @fires gla_documentation_link_click with `{ context: 'edit-ads', link_id: 'eu-political-content', href: 'https://support.google.com/adspolicy/answer/6014595' }`
 *
 * @param {Object} props React props.
 * @param {'setup-ads'|'create-ads'|'edit-ads'} props.context A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 */
const EuPoliticalContentCard = ( { context } ) => {
	const { getInputProps } = useAdaptiveFormContext();
	const inputProps = getInputProps( 'hasConfirmedEuPoliticalContent' );

	return (
		<Section.Card className="gla-eu-political-content-card">
			<Section.Card.Body>
				<Section.Card.Title>
					{ __( 'EU political content', 'google-listings-and-ads' ) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					<CheckboxControl
						{ ...inputProps }
						help={ __(
							"If selected, your ads will not run in the EU unless you complete Google's political advertiser verification.",
							'google-listings-and-ads'
						) }
						label={ createInterpolateElement(
							__(
								"My ads include political content as defined by Google's <link>EU political content policy</link>.",
								'google-listings-and-ads'
							),
							{
								link: (
									<AppDocumentationLink
										context={ context }
										href="https://support.google.com/adspolicy/answer/6014595"
										linkId="eu-political-content"
									/>
								),
							}
						) }
					/>

					{ inputProps.checked && (
						<Notice isDismissible={ false } status="error">
							{ __(
								'Your ads will not run in the EU',
								'google-listings-and-ads'
							) }
						</Notice>
					) }
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default EuPoliticalContentCard;
