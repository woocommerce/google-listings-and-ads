/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Tag } from '@woocommerce/components';

/**
 * A list of tags to display selected items.
 *
 * @param {Object} props The component props
 * @param {Object[]} props.tags The tags
 * @param {Function} props.onChange The method called when a tag is removed
 * @param {boolean} props.disabled True if the plugin is disabled
 */
const Tags = ( { tags, disabled, onChange = () => {} } ) => {
	if ( ! tags.length ) {
		return null;
	}

	/**
	 * Callback to remove a Tag.
	 * The function is defined this way because in the WooCommerce Tag Component the remove logic
	 * is defined as `onClick={ remove(key) }` hence we need to do this to avoid calling remove function
	 * on each render.
	 *
	 * @param {string} key The key for the Tag to be deleted
	 */
	const remove = ( key ) => {
		return () => {
			if ( disabled ) {
				return;
			}
			onChange( tags.filter( ( tag ) => tag.id !== key ) );
		};
	};

	return (
		<div className="woocommerce-tree-select-control__tags">
			{ tags.map( ( item, i ) => {
				if ( ! item.name ) {
					return null;
				}
				const screenReaderLabel = sprintf(
					// translators: 1: Tag Name, 2: Current Tag index, 3: Total amount of tags.
					__( '%1$s (%2$s of %3$s)', 'woocommerce-admin' ),
					item.name,
					i + 1,
					tags.length
				);
				return (
					<Tag
						key={ item.id }
						id={ item.id }
						label={ item.name }
						screenReaderLabel={ screenReaderLabel }
						remove={ remove }
					/>
				);
			} ) }
		</div>
	);
};

export default Tags;
