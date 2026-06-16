# State Management

GLA uses `@wordpress/data` with a single custom store (`wc/gla`) for all plugin state.

## Store Key

```js
import { STORE_KEY } from '~/data/constants'; // 'wc/gla'
import { API_NAMESPACE } from '~/data/constants'; // '/wc/gla'
```

## Data Layer Files (`js/src/data/`)

| File | Purpose |
|---|---|
| `action-types.js` | `TYPES` object — string constants for all action types |
| `actions.js` | Action creators; call `apiFetch` from `@wordpress/data-controls` |
| `reducer.js` | Pure state transitions keyed by action type |
| `selectors.js` | Memoized state accessors via `createSelector` (rememo) |
| `resolvers.js` | Auto-fired on `select()`; generator functions that dispatch fetch actions |
| `adapters.js` | Transforms API snake_case responses to camelCase store state |
| `controls.js` | Custom controls extending `@wordpress/data-controls` |
| `apiFetchMiddlewares.js` | Global API fetch middleware (401 redirect) |

## Store Initialization (`js/src/data/index.js`)

```js
import { registerStore, dispatch } from '@wordpress/data';
import { glaData } from '~/constants';
import { STORE_KEY } from './constants';

registerStore( STORE_KEY, { actions, selectors, resolvers, controls, reducer } );

// Hydrate with PHP-prefetched data
dispatch( STORE_KEY ).hydratePrefetchedData( glaData.initialWpData );
```

## Reading State

```js
import { useAppSelectDispatch } from '~/hooks/useAppSelectDispatch';

// Returns { data, isResolving, hasFinishedResolution, invalidateResolution }
const { data: campaigns, isResolving } = useAppSelectDispatch( 'getAdsCampaigns' );

// Multi-store access — use useSelect directly
import { useSelect } from '@wordpress/data';
const count = useSelect( ( select ) => {
    const gla = select( STORE_KEY );
    const notices = select( 'core/notices' );
    return { ads: gla.getAdsCampaigns(), notices: notices.getNotices() };
} );
```

## Writing State

```js
import { useAppDispatch } from '~/data';

// In component body — never inside useSelect
const { updateAdsCampaign } = useAppDispatch();

const handleSave = async () => {
    await updateAdsCampaign( campaignId, { budget } );
};
```

**Never dispatch inside `useSelect`.**

## Adding State

1. Add a type constant to `action-types.js`: `RECEIVE_MY_DATA: 'RECEIVE_MY_DATA'`
2. Add action creator to `actions.js`:
   ```js
   export function* getMyData() {
       const data = yield apiFetch( { path: `${ API_NAMESPACE }/my-endpoint` } );
       yield { type: TYPES.RECEIVE_MY_DATA, data };
   }
   ```
3. Add reducer case:
   ```js
   case TYPES.RECEIVE_MY_DATA:
       return { ...state, myData: action.data };
   ```
4. Add selector:
   ```js
   export const getMyData = ( state ) => state.myData;
   ```
5. Add resolver in `resolvers.js` if data should auto-fetch when first selected

## 401 Middleware

`apiFetchMiddlewares.js` intercepts 401 responses from GLA API calls:
- If `glaData.mcSetupComplete` is true and status is 401, redirects to the reconnect account URL
- Uses `getReconnectAccountUrl( errorInfo.code )` from `~/utils/urls` to determine the target URL
