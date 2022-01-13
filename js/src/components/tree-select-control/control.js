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

const Control = () => {
	return (
		<div
			className={ classnames(
				'components-base-control',
				'woocommerce-tree-select-control__control'
			) }
		>
			<div className="components-base-control__field">
				<input
					type="search"
					autoComplete="off"
					className="woocommerce-tree-select-control__control-input"
					role="combobox"
					aria-autocomplete="list"
					// aria-expanded={ isExpanded }
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
