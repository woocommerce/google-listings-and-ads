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

const ConnectedIconLabel = ( props ) => {
	const { className } = props;

	return (
		<Flex
			align="center"
			className={ classnames( 'gla-connected-icon-label', className ) }
			gap={ 1 }
		>
			<FlexItem>
				<GridiconCheckmarkCircle />
			</FlexItem>
			<FlexItem>
				{ __( 'Connected', 'google-listings-and-ads' ) }
			</FlexItem>
		</Flex>
	);
};

export default ConnectedIconLabel;
