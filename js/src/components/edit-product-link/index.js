/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { queueRecordEvent } from '@woocommerce/tracks';

const noop = () => {};

/**
 * Renders a link that points to the product edit page.
 * Click event tracking would be attached if giving the `eventName` prop.
 *
 * @param {Object} props React props.
 * @param {number} props.productId Product ID.
 * @param {string} [props.eventName] The event name to record.
 * @param {Object} [props.eventProps] Event properties to include in the event.
 */
const EditProductLink = ( { productId, eventName, eventProps } ) => {
	const editProductLink = `post.php?action=edit&post=${ productId }`;

	const handleClick = eventName
		? () => queueRecordEvent( eventName, eventProps )
		: noop;

	return (
		<Link href={ editProductLink } onClick={ handleClick } type="wp-admin">
			{ __( 'Edit', 'google-listings-and-ads' ) }
		</Link>
	);
};

export default EditProductLink;
