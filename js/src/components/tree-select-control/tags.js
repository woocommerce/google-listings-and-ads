/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Tag } from '@woocommerce/components';

/**
 * A list of tags to display selected items.
 *
 * @param {Object[]} tags The tags
 * @param {Function} onChange The method called when a tag is removed
 */
const Tags = ( { tags, onChange = () => {} } ) => {
	if ( ! tags.length ) {
		return null;
	}

	const remove = ( key ) => {
		return () => {
			const i = tags.findIndex( ( tag ) => tag.id === key );
			onChange( [ ...tags.slice( 0, i ), ...tags.slice( i + 1 ) ] );
		};
	};

	return (
		<div className="woocommerce-select-control__tags">
			{ tags.map( ( item, i ) => {
				if ( ! item.name ) {
					return null;
				}
				const screenReaderLabel = sprintf(
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
						remove={ remove }
						screenReaderLabel={ screenReaderLabel }
					/>
				);
			} ) }
		</div>
	);
};

export default Tags;
