# Routing and Navigation

GLA uses WooCommerce admin's query-string routing — there is no React Router.

## URL Helpers (`js/src/utils/urls.js`)

All GLA page paths are defined as constants — never hardcode paths:

```js
import { pagePaths, subpaths, getEditCampaignUrl, getCreateCampaignUrl, getDashboardUrl } from '~/utils/urls';

// Page paths
pagePaths.getStarted    // '/google/start'
pagePaths.onboarding    // '/google/setup-mc'
pagePaths.adsOnboarding // '/google/setup-ads'
pagePaths.dashboard     // '/google/dashboard'
pagePaths.reports       // '/google/reports'
pagePaths.productFeed   // '/google/product-feed'
pagePaths.settings      // '/google/settings'
pagePaths.shipping      // '/google/shipping'

// Subpaths (appended as query param, not real URL segments)
subpaths.editCampaign           // '/campaigns/edit'
subpaths.createCampaign         // '/campaigns/create'
subpaths.editStoreAddress       // '/edit-store-address'
subpaths.reconnectWPComAccount  // '/reconnect-wpcom-account'
subpaths.reconnectGoogleAccount // '/reconnect-google-account'
```

## Navigation

```js
import { getHistory, getNewPath } from '@woocommerce/navigation';

// Navigate programmatically — never window.location.href
getHistory().replace( getDashboardUrl() );
getHistory().push( getNewPath( { subpath: subpaths.editCampaign, programId: 123 }, pagePaths.dashboard ) );

// Build URL with query params
const url = getNewPath( { query: 'value' }, pagePaths.reports, null );
```

## Page Registration (`js/src/index.js`)

Pages are registered via WooCommerce filter:

```js
addFilter( 'woocommerce_admin_pages_list', namespace, ( pages ) => {
    return [
        ...pages,
        {
            breadcrumbs: [ [ '', 'WooCommerce' ], [ '/marketing', 'Marketing' ], 'Google for WooCommerce' ],
            container: MyPage,
            path: '/google/my-page',
            wpOpenMenu: 'toplevel_page_woocommerce-marketing',
        },
    ];
} );
```

## Lazy Loading

Every page component must be lazy-loaded with a `webpackChunkName` comment:

```js
import { lazy } from '@wordpress/element';

const MyPage = lazy( () =>
    import( /* webpackChunkName: "my-page" */ './pages/my-page' )
);
```

Pages are wrapped with `withAdminPageShell` HOC for consistent layout, breadcrumbs, and error boundaries.

## Breadcrumb Format

```js
breadcrumbs: [
    [ '', woocommerceTranslation ],                              // root
    [ '/marketing', __( 'Marketing', 'google-listings-and-ads' ) ],
    __( 'Google for WooCommerce', 'google-listings-and-ads' ),   // current page (string, no link)
]
```

For sub-pages, append additional items to the array.

## Subpath Navigation

Subpaths are query parameters, not real URL segments. They allow multiple "sub-pages" under a single WC admin page:

```js
// Navigate to campaign edit sub-page
getHistory().push( getEditCampaignUrl( campaignId ) );
// Result: /google/dashboard?subpath=/campaigns/edit&programId=123

// Read current subpath in component
import { getQuery } from '@woocommerce/navigation';
const { subpath } = getQuery();
```
