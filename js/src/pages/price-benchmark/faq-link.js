/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';

const URL = 'https://woocommerce.com/document/google-for-woocommerce/faq/';

/**
 * Renders an external FAQ link and tracks click events for analytics.
 *
 * @fires gla_documentation_link_click with `{ context: 'price-benchmark-suggestions', link_id: 'price-benchmark-suggestions-faq' }` and the URL.
 * @return {JSX.Element} The rendered external FAQ link component.
 */
const FaqLink = () => {
	const handleClick = () => {
		recordGlaEvent( 'gla_documentation_link_click', {
			context: 'price-benchmark-suggestions',
			link_id: 'price-benchmark-suggestions-faq',
			href: URL,
		} );
	};

	return (
		<ExternalLink
			onClick={ handleClick }
			href={ URL }
			className="gla-price-benchmark-suggestions__faq"
		>
			{ __( 'FAQ', 'google-listings-and-ads' ) }
		</ExternalLink>
	);
};

export default FaqLink;
