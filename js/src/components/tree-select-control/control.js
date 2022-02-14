/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BACKSPACE, DOWN, UP } from '@wordpress/keycodes';
import { createElement, Component, createRef } from '@wordpress/element';
import { Icon, search } from '@wordpress/icons';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Tags from './tags';

const Control = ( {
	label,
	instanceId,
	placeholder,
	isExpanded,
	setExpanded,
} ) => {
	const id = `woocommerce-select-control-${ instanceId }__control-input`;

	return (
		<div
			className={ classnames(
				'components-base-control',
				'woocommerce-tree-select-control__control'
			) }
		>
			<div className="components-base-control__field">
				{ !! label && (
					<label
						htmlFor={ id }
						className="components-base-control__label"
					>
						{ label }
					</label>
				) }
				<input
					id={ id }
					type="search"
					placeholder={ isExpanded ? '' : placeholder }
					autoComplete="off"
					className="woocommerce-tree-select-control__control-input"
					role="combobox"
					aria-autocomplete="list"
					aria-expanded={ isExpanded }
					aria-haspopup="true"
					// aria-owns={ listboxId }
					// aria-controls={ listboxId }
					// aria-activedescendant={ activeId }
					// aria-describedby={
					// 	hasTags && inlineTags
					// 		? `search-inline-input-${ instanceId }`
					// 		: null
					// }
				/>
			</div>
		</div>
	);
};

export default Control;
