/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';

export const YOUTUBE_MERCHANT_TERMS_URL =
	'https://www.youtube.com/t/merchant_terms';

/**
 * Renders the YouTube Merchant Terms link and records the documentation click.
 *
 * @param {Object} props Component props.
 * @param {string} [props.context='settings-connect-youtube-account-card'] Tracking context.
 * @param {string} [props.className] Optional class name.
 * @return {JSX.Element} The external link.
 */
export default function YouTubeMerchantTermsLink( {
	context = 'settings-connect-youtube-account-card',
	className,
} ) {
	const handleClick = () => {
		recordGlaEvent( 'gla_documentation_link_click', {
			context,
			link_id: 'youtube-merchant-terms',
			href: YOUTUBE_MERCHANT_TERMS_URL,
		} );
	};

	return (
		<ExternalLink
			className={ className }
			onClick={ handleClick }
			href={ YOUTUBE_MERCHANT_TERMS_URL }
		>
			{ __( 'YouTube Merchant Terms', 'google-listings-and-ads' ) }
		</ExternalLink>
	);
}
