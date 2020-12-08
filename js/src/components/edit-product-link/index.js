/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

const EditProductLink = ( props ) => {
	const { productId } = props;
	const editProductLink = `post.php?action=edit&post=${ productId }`;

	return (
		<Link href={ editProductLink } type="wp-admin">
			{ __( 'Edit', 'google-listings-and-ads' ) }
		</Link>
	);
};

export default EditProductLink;
