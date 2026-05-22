/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TrackableLink from '~/components/trackable-link';
import { getSettingsUrl } from '~/utils/urls';

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
	return (
		<Notice status="warning" isDismissible={ false }>
			{ createInterpolateElement(
				__(
					'Your current shipping setup is not compatible with multilingual feeds. You have "I will manually enter my shipping rates" selected. To use multilingual feeds, switch to a different shipping setup in <link>Settings</link>.',
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
