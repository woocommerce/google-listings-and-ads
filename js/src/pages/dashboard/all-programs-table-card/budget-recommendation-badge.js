/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { Icon, trendingUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';

const BudgetRecommendationBadge = () => {
	return (
		<Badge intent="success">
			<Flex align="center" gap={ 1 } justify="flex-start">
				<Icon icon={ trendingUp } width={ 16 } />
				{ __( 'Budget recommendation', 'google-listings-and-ads' ) }
			</Flex>
		</Badge>
	);
};

export default BudgetRecommendationBadge;
