/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';
import GridiconCheckmarkCircle from 'gridicons/dist/checkmark-circle';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a text with a leading checkmark circle icon.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {string} [props.text] The text to be displayed.
 */
const ConnectedIconLabel = ( {
	className,
	text = __( 'Connected', 'google-listings-and-ads' ),
} ) => {
	return (
		<Flex
			className={ classnames( 'gla-connected-icon-label', className ) }
			align="center"
			gap={ 1 }
		>
			<FlexItem>
				<GridiconCheckmarkCircle />
			</FlexItem>
			<FlexItem>{ text }</FlexItem>
		</Flex>
	);
};

export default ConnectedIconLabel;
