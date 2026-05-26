/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getSettingsUrl } from '~/utils/urls';
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import TrackableLink from '~/components/trackable-link';
import useSettings from '~/hooks/useSettings';
import './index.scss';

/**
 * @event gla_multilingual_flat_notice_settings_link_click
 */

/**
 * Displays a warning notice when the store uses flat shipping rates,
 * which are incompatible with multilingual feeds.
 *
 * @fires gla_multilingual_flat_notice_settings_link_click When the Settings link in the notice is clicked.
 */
const MultilingualFlatShippingNotice = () => {
	const { settings } = useSettings();
	const shippingRate = settings?.shipping_rate;

	if (
		! glaData.isMultiLingualStore ||
		shippingRate !== SHIPPING_RATE_METHOD.FLAT
	) {
		return null;
	}

	return (
		<Notice
			status="warning"
			isDismissible={ false }
			className="gla-multilingual-flat-shipping-notice"
		>
			{ createInterpolateElement(
				__(
					'Your current shipping setup is not compatible with multilingual feeds. You have "My shipping settings are simple. I can manually estimate flat shipping rates" selected. To use multilingual feeds, switch to a different shipping setup in <link>Settings</link>.',
					'google-listings-and-ads'
				),
				{
					link: (
						<TrackableLink
							type="wc-admin"
							href={ getSettingsUrl() }
							eventName="gla_multilingual_flat_notice_settings_link_click"
						/>
					),
				}
			) }
		</Notice>
	);
};

export default MultilingualFlatShippingNotice;
