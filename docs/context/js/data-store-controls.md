# Data Store Controls

GLA extends `@wordpress/data-controls` with three custom controls defined in `js/src/data/controls.js`.

## FETCH_WITH_HEADERS

Use when you need response headers (e.g., pagination totals in `X-WP-Total`).

```js
import { fetchWithHeaders } from '~/data/controls';

// In a resolver or action (generator function)
export function* getAdsCampaigns() {
    const { headers, status, data } = yield fetchWithHeaders( {
        path: `${ API_NAMESPACE }/ads/campaigns`,
        method: 'GET',
    } );

    const total = headers.get( 'X-WP-Total' );
    yield { type: TYPES.RECEIVE_ADS_CAMPAIGNS, campaigns: data, total };
}
```

The control calls `apiFetch({ ...options, parse: false })` and resolves with `{ headers, status, data }` where `data` is the parsed JSON body.

## GLA_AWAIT_PROMISE

Use when you need to await an arbitrary promise inside a generator (not an `apiFetch` call).

```js
import { awaitPromise } from '~/data/controls';

export function* processData() {
    const result = yield awaitPromise( someAsyncOperation() );
    yield { type: TYPES.RECEIVE_RESULT, result };
}
```

## GLA_RECORD_DATA_EVENT

Fires a Tracks analytics event from within a resolver or action. Used internally by resolvers that receive budget data.

```js
import { recordGlaDataEvent } from '~/data/controls';

export function* getBudgetRecommendations( params ) {
    const data = yield apiFetch( { path: `${ API_NAMESPACE }/ads/budget` } );
    yield recordGlaDataEvent( TYPES.RECEIVE_ADS_BUDGET_RECOMMENDATIONS, data );
    yield { type: TYPES.RECEIVE_ADS_BUDGET_RECOMMENDATIONS, ...data };
}
```

Currently fires events for:
- `RECEIVE_ADS_BUDGET_RECOMMENDATIONS` → `gla_ads_budget_recommendations_received`
- `RECEIVE_ADS_BUDGET_METRICS` → `gla_ads_budget_metrics_received`

## Import Source

Always import control creators from `~/data/controls`, not from `@wordpress/data-controls`:

```js
// Correct
import { fetchWithHeaders, awaitPromise, recordGlaDataEvent } from '~/data/controls';

// Wrong
import { apiFetch } from '@wordpress/data-controls'; // this is for apiFetch yielding, not the custom controls
```

The `controls` export from `controls.js` spreads `@wordpress/data-controls` first, so all standard controls (`apiFetch`, `select`, `dispatch`, etc.) are also available in resolvers and actions.
