# Custom product blocks

To make this extension compatible with the Product Block Editor, it needs to implement a few custom blocks to map all classical fields to product editor blocks.

## Development

Implementing custom blocks by the default development way, each block will be built as a separate script and will need to be loaded into the browser separately at runtime.

Since this extension requires a few custom blocks, and considering these custom blocks will not be used individually, and in order to reduce the number of scripts loaded, here are some adjustments that differ from the default way.

### Directory structure of source code

```
/js/src/blocks/            # The root directory of custom blocks
├── product-select-field/  # A custom block
│   ├── block.json         # The metadata file of this block and also the canonical way to register this block with both PHP and JavaScript side
│   └── edit.js            # The component to work with the `edit` function when registering this block, and it's the interface for how this block is going to be rendered within the Product Block Editor
├── another-field/         # Another custom block
│   ├── block.json
│   └── edit.js
├── components/            # The shared components within custom blocks
│   ├── index.js           # The main file to export components
│   └── label.js           # A shared component
└── index.js               # The main file to import and register all custom blocks via JavaScript
```

### Directory structure of built code

```
/js/build/                 # The root directory of built code
├── product-select-field/  # A custom block
│   └── block.json         # The copied metadata file to be used to register this block via PHP
├── another-field/         # Another custom block
│   └── block.json
├── blocks.js              # The built script to register all custom blocks via JavaScript and to be registered and enqueued via PHP
└── blocks.asset.php       # The dependencies of blocks.js to be used when registering blocks.js via PHP
```

### Compatible with block templates and product data access

The Product Block Editor requires template attributes and `usesContext` to support its specific features, e.g. conditionally hide/disable block or contextually access product data. To make the custom blocks fully compatible with these features, the uses of the following APIs are required:

- JavaScript side:
   - Use `useWooBlockProps` hook imported from `@woocommerce/block-templates` to get React props for the custom block's `<div>` wrapper.
   - Use `useProductEntityProp` hook imported from `@woocommerce/product-editor` to get and set product data or metadata.
   - Forwarding the `context.postType` to `useProductEntityProp` hook to contextually access simple or variation product.
- PHP side:
   - Register all custom blocks via `\Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry::get_instance()->register_block_type_from_metadata()`.

#### Derived value for initialization

The "derived value" refers to the computation of a value based on another state or props in a component. At the time of starting rendering a block, the product data has already been loaded to a data store of `@wordpress/data` in Woo's Product Block Editor, so the value returned from `useProductEntityProp` can be considered as an already fetched data for directly initializing derived values, because they all eventually use the same selector `getEntityRecord` from `@wordpress/core-data` to get product data.

References:

- At [ProductPage](https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce-admin/client/products/product-page.tsx#L77-L79) and [ProductVariationPage](https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce-admin/client/products/product-variation-page.tsx#L83-L85) layers, they won't render the actual blocks before the product data is fetched
- The above product data is obtained from [useProductEntityRecord](https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce-admin/client/products/hooks/use-product-entity-record.ts#L19-L23) or [useProductVariationEntityRecord](https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce-admin/client/products/hooks/use-product-variation-entity-record.ts#L16-L22) hook, and both hooks use the `getEntityRecord` selector.
- This extension obtains product data from [useProductEntityProp](https://github.com/woocommerce/woocommerce/blob/8.3.0/packages/js/product-editor/src/hooks/use-product-entity-prop.ts#L24-L33), which uses the `useEntityProp` hook internally.
- The [useEntityProp](https://github.com/WordPress/gutenberg/blob/wp/6.0/packages/core-data/src/entity-provider.js#L102-L133) hook also uses the `getEntityRecord` selector.

### Infrastructure adjustments

#### block.json and edit.js

By default, it should specify the `editorScript` with the relative path of edit.js, so that the `wp-scripts build` and `wp-scripts start` provided by `@wordpress/scripts` will also parse and build each block separately, and the edit.js needs to register itself along with its block.json.

With the adjusted setup:

- Every block.json should **not** specify the `editorScript` to avoid building blocks separately.
- Every edit.js should **not** do the registration.
- All block.json and edit.js files should be imported and registered via JavaScript by the index.js file at the root directory of custom blocks.

#### webpack.config.js

By default, when building, the [webpack config](https://github.com/WordPress/gutenberg/tree/%40wordpress/scripts%4024.6.0/packages/scripts#default-webpack-config) imported from `@wordpress/scripts` will find all block.json files within the given source code directory [specified by `--webpack-src-dir`](https://github.com/WordPress/gutenberg/tree/%40wordpress/scripts%4024.6.0/packages/scripts#automatic-blockjson-detection-and-the-source-code-directory), and then parse and build each block as per block.json.

With the adjusted setup:

- Build the index.js file in the root directory of custom blocks as the blocks.js at the root directory of built code.
- The blocks.asset.php file will be generated along with the build of the blocks.js. The `dependencies` value in the file is related to the mechanism of DEWP. See [Working with DEWP.md](../../../Working%20with%20DEWP.md) for more details.

#### Registration on the PHP side

By default, when each block is registered via PHP in the [ProductBlocksService](../../../src/Admin/ProductBlocksService.php) class, the `register_block_type` function will also register and enqueue the related scripts and styles such as the built edit.js.

The same part is the separate registration for each block, therefore all blocks should be listed in the `CUSTOM_BLOCKS` array of the `ProductBlocksService` class.

With the adjusted setup:

- The scripts and styles of blocks are not specified in block.json files, so they won't be registered or enqueued via the `register_block_type` function.
- Instead, the blocks.js script is registered and enqueued by `ProductBlocksService`.

## Related documentation

- [Woo - Product Editor Development](https://github.com/woocommerce/woocommerce/tree/trunk/docs/product-editor-development)
- [WordPress - Block Editor Handbook](https://developer.wordpress.org/block-editor/)
