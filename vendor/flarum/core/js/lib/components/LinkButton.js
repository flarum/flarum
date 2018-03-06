import Button from 'flarum/components/Button';

/**
 * The `LinkButton` component defines a `Button` which links to a route.
 *
 * ### Props
 *
 * All of the props accepted by `Button`, plus:
 *
 * - `active` Whether or not the page that this button links to is currently
 *   active.
 * - `href` The URL to link to. If the current URL `m.route()` matches this,
 *   the `active` prop will automatically be set to true.
 */
export default class LinkButton extends Button {
  static initProps(props) {
    props.active = this.isActive(props);
    props.config = props.config || m.route;
  }

  view() {
    const vdom = super.view();

    vdom.tag = 'a';

    return vdom;
  }

  /**
   * Determine whether a component with the given props is 'active'.
   *
   * @param {Object} props
   * @return {Boolean}
   */
  static isActive(props) {
    return typeof props.active !== 'undefined'
      ? props.active
      : m.route() === props.href;
  }
}
