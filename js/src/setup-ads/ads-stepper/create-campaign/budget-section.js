/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppInputControl from '.~/components/app-input-control';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import getMonthlyMaxEstimated from './getMonthlyMaxEstimated';

const BudgetSection = ( props ) => {
	const {
		formProps: { getInputProps, values },
	} = props;
	const { code: currencyCode } = useStoreCurrency();

	const monthlyMaxEstimated = getMonthlyMaxEstimated( values.amount );

	return (
		<Section
			title={ __( 'Budget', 'google-listings-and-ads' ) }
			description={
				<p>
					{ __(
						'Enter a daily average cost that works best for your business and the results that you want. You can change your budget or cancel your ad at any time.',
						'google-listings-and-ads'
					) }
				</p>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<AppInputControl
						label={ __(
							'Daily average cost',
							'google-listings-and-ads'
						) }
						suffix={ currencyCode }
						{ ...getInputProps( 'amount' ) }
					/>
					<AppInputControl
						disabled
						label={ __(
							'Monthly max, estimated ',
							'google-listings-and-ads'
						) }
						suffix={ currencyCode }
						value={ monthlyMaxEstimated }
					/>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default BudgetSection;
