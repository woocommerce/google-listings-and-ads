/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import { ADVERTISING_SOURCE } from '~/constants';
import './index.scss';

const TITLE = __( 'What do you want to advertise?', 'google-listings-and-ads' );

const OPTIONS = [
	{
		value: ADVERTISING_SOURCE.PRODUCT,
		label: __( 'My products', 'google-listings-and-ads' ),
		description: __(
			'This campaign will use information from the product feed you’ve synced with Google Merchant Center.',
			'google-listings-and-ads'
		),
	},
	{
		value: ADVERTISING_SOURCE.PAGE,
		label: __( 'Another page on my store', 'google-listings-and-ads' ),
		description: __(
			'Advertise a different page on your store, such as a service booking page or your homepage.',
			'google-listings-and-ads'
		),
	},
];

/**
 * Renders the card for choosing what the campaign advertises: the products synced to Merchant Center, or a page on the store.
 *
 * It expects a `CampaignAssetsForm` to exist in its parents. The parent decides whether to render it, since it only
 * applies with a Merchant Center connection.
 *
 * @return {JSX.Element} The rendered component.
 */
export default function AdvertisingSourceSelector() {
	const { getInputProps } = useAdaptiveFormContext();

	// `checked` is meant for checkboxes. Leave it out so it isn't passed down to `RadioControl`.
	// eslint-disable-next-line no-unused-vars
	const { value, checked, ...radioProps } =
		getInputProps( 'advertisingSource' );

	return (
		<Section.Card className="gla-advertising-source-selector">
			<Section.Card.Body>
				<Section.Card.Title>{ TITLE }</Section.Card.Title>
				<Subsection.Subtitle>
					{ __(
						'Choose between your products or a page from your store.',
						'google-listings-and-ads'
					) }
				</Subsection.Subtitle>
				<RadioControl
					label={ TITLE }
					options={ OPTIONS }
					selected={ value }
					{ ...radioProps }
					hideLabelFromVision
				/>
			</Section.Card.Body>
		</Section.Card>
	);
}
