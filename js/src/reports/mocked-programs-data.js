import { getQuery } from '@woocommerce/navigation';


const freeListings = [{
    id: 147,
    title: 'Google Free Listings',
    conversions: 89,
    clicks: 5626,
    impressions: 23340,
    itemsSold: 120,
    netSales: "$96.73",
    spend: "$0",
    compare: false,
}];
const freeListingsMissingData = freeListings.map( ( program ) => {
    return {
        ...program,
        conversions: null,
        itemsSold: null,
        netSales: null,
    }
});

const paidPrograms = [
    {
        id: 123,
        title: 'Google Smart Shopping: Fall',
        conversions: 540,
        clicks: 4152,
        impressions: 14339,
        itemsSold: 1033,
        netSales: "$2,527.91",
        spend: "$300",
        compare: false,
    },
    {
        id: 456,
        title: 'Google Smart Shopping: Core',
        conversions: 357,
        clicks: 1374,
        impressions: 43359,
        itemsSold: 456,
        netSales: "$6,204.16",
        spend: "$200",
        compare: false,
    },
    {
        id: 789,
        title: 'Google Smart Shopping: Black Friday',
        conversions: 426,
        clicks: 3536,
        impressions: 92771,
        itemsSold: 877,
        netSales: "$2,091.05",
        spend: "$100",
        compare: false,
    },
]

/**
 * Returns mocked Listings Data, according to missingFreeListingsData query parameter.
 *
 * not set or "" - mark all metrics as not missing data.
 * "true" or any truthy value - mark some metrics as missing data from free listings.
 * "na" - not applicable, do not provide metrics that would miss data.
 */
export function mockedListingsData(){
    const { missingFreeListingsData = false } = getQuery();

    if( missingFreeListingsData == 'na' ) {
        return freeListingsMissingData;
    } else if ( missingFreeListingsData ) {
        return paidPrograms.concat( freeListingsMissingData );
    } else {
        return paidPrograms.concat( freeListings );
    }
}

export const getProgramLabels = function() {
 return paidPrograms.concat( freeListingsMissingData );
}

/**
 * Returns mocked available metric values, according to missingFreeListingsData query parameter.
 *
 * not set or "" - mark all metrics as not missing data.
 * "true" or any truthy value - mark some metrics as missing data from free listings.
 * "na" - not applicable, do not provide metrics that would miss data.
 */
export function availableMetrics(){
    const { missingFreeListingsData = false } = getQuery();

    const availableMetrics = [ 'clicks', 'impressions', 'spend' ];
    const conditionalMetrics = [ 'netSales', 'itemsSold', 'conversions' ];

    if( missingFreeListingsData == 'na' ) {
        return availableMetrics;
    } else {
        return conditionalMetrics.concat( availableMetrics )
    }
}
