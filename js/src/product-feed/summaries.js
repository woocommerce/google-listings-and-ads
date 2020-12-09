/**
 * External dependencies
 */
import { SummaryList, SummaryNumber } from '@woocommerce/components';

// TODO: the data here should be coming from backend API.
const data = [
	{
		title: 'Active / Partially Active',
		value: 0,
	},
	{
		title: 'Expiring',
		value: 0,
	},
	{
		title: 'Pending',
		value: 149,
	},
	{
		title: 'Disapproved',
		value: 0,
	},
	{
		title: 'Not Synced',
		value: 4,
	},
];

const Summaries = () => {
	return (
		<SummaryList>
			{ () => {
				return data.map( ( el, idx ) => {
					return (
						<SummaryNumber
							key={ idx }
							label={ el.title }
							value={ el.value }
						/>
					);
				} );
			} }
		</SummaryList>
	);
};

export default Summaries;
