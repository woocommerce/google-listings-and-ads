/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import classnames from 'classnames';
// import { __, _n, sprintf } from '@wordpress/i18n';
// import { Component, createElement } from '@wordpress/element';
// import { debounce, escapeRegExp, identity, noop } from 'lodash';
// import PropTypes from 'prop-types';
// import { withFocusOutside, withSpokenMessages } from '@wordpress/components';
// import { withInstanceId, compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import List from './list';
import Control from './control';
import './index.scss';

const TreeSelectControl = ( props ) => {
	const { className, disabled, value = [], onChange = () => {} } = props;
	const [ isExpanded, setIsExpanded ] = useState( true );

	return (
		<div
			className={ classnames(
				'woocommerce-tree-select-control',
				className
			) }
		>
			<Control
				disabled={ disabled }
				hasTags
				isExpanded={ isExpanded }
				// onSearch={ this.search }
				// selected={ this.getSelected() }
				onChange={ onChange }
				setExpanded={ setIsExpanded }
				// updateSearchOptions={ this.updateSearchOptions }
				// decrementSelectedIndex={ this.decrementSelectedIndex }
				// incrementSelectedIndex={ this.incrementSelectedIndex }
			/>
			{ /* { isExpanded && (
				<List
					node={ this.node }
					onSelect={ this.selectOption }
					onSearch={ this.search }
					options={ this.getOptions() }
					decrementSelectedIndex={ this.decrementSelectedIndex }
					incrementSelectedIndex={ this.incrementSelectedIndex }
					setExpanded={ this.setExpanded }
				/>
			) } */ }
		</div>
	);
};

export default TreeSelectControl;
