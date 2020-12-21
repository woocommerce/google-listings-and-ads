# FullScreen

Make the wrapped component display in full screen. It hides the top bar and left side bar by applying `woocommerce-admin-full-screen` and `app-full-screen` CSS class to the `document.body`.

`woocommerce-admin-full-screen` is an existing CSS class from the `woocommerce-admin` extension. ([Source](https://github.com/woocommerce/woocommerce-admin/blob/main/client/layout/style.scss#L47))

## Usage

```jsx
<FullScreen>
  <MyComponent>
</FullScreen>
```
