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
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { ADVERTISING_SOURCE } from '~/constants';
import './advertising-source-selector.scss';

const OPTIONS = [
	{
		value: ADVERTISING_SOURCE.PRODUCT,
		label: __( 'My products', 'google-listings-and-ads' ),
		help: __(
			"This campaign will use information from the product feed you've synced with Google Merchant Center.",
			'google-listings-and-ads'
		),
	},
	{
		value: ADVERTISING_SOURCE.PAGE,
		label: __( 'Another page on my store', 'google-listings-and-ads' ),
		help: __(
			'Advertise a different page on your store, such as a service booking page or your homepage.',
			'google-listings-and-ads'
		),
	},
];

/**
 * Renders the card for choosing what the campaign advertises: the products synced to Merchant Center, or a page on the store.
 *
 * It expects a `CampaignAssetsForm` to exist in its parents. Without a Merchant Center connection it renders nothing,
 * since the form then defaults the advertising source to a page.
 *
 * @return {JSX.Element|null} The rendered component, or null without a Merchant Center connection.
 */
export default function AdvertisingSourceSelector() {
	const { getInputProps } = useAdaptiveFormContext();
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	if ( ! hasGoogleMCConnection ) {
		return null;
	}

	// `checked` is meant for checkboxes. Leave it out so it isn't passed down to `RadioControl`.
	// eslint-disable-next-line no-unused-vars
	const { value, checked, ...radioProps } =
		getInputProps( 'advertisingSource' );

	return (
		<Section.Card className="gla-advertising-source-selector">
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'What do you want to advertise?',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<Subsection.Subtitle>
					{ __(
						'Your products synced to Merchant Center, or a page from your store.',
						'google-listings-and-ads'
					) }
				</Subsection.Subtitle>
				<div className="gla-advertising-source-selector__options">
					{ OPTIONS.map( ( option ) => (
						<RadioControl
							{ ...radioProps }
							key={ option.value }
							className="gla-advertising-source-selector__option"
							options={ [
								{ label: option.label, value: option.value },
							] }
							help={ option.help }
							selected={ value }
						/>
					) ) }
				</div>
			</Section.Card.Body>
		</Section.Card>
	);
}
