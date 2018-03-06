/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The `Component` class defines a user interface 'building block'. A component
 * can generate a virtual DOM to be rendered on each redraw.
 *
 * An instance's virtual DOM can be retrieved directly using the {@link
 * Component#render} method.
 *
 * @example
 * this.myComponentInstance = new MyComponent({foo: 'bar'});
 * return m('div', this.myComponentInstance.render());
 *
 * Alternatively, components can be nested, letting Mithril take care of
 * instance persistence. For this, the static {@link Component.component} method
 * can be used.
 *
 * @example
 * return m('div', MyComponent.component({foo: 'bar'));
 *
 * @see https://lhorie.github.io/mithril/mithril.component.html
 * @abstract
 */
export default class Component {
  /**
   * @param {Object} props
   * @param {Array|Object} children
   * @public
   */
  constructor(props = {}, children = null) {
    if (children) props.children = children;

    this.constructor.initProps(props);

    /**
     * The properties passed into the component.
     *
     * @type {Object}
     */
    this.props = props;

    /**
     * The root DOM element for the component.
     *
     * @type DOMElement
     * @public
     */
    this.element = null;

    /**
     * Whether or not to retain the component's subtree on redraw.
     *
     * @type {boolean}
     * @public
     */
    this.retain = false;

    this.init();
  }

  /**
   * Called when the component is constructed.
   *
   * @protected
   */
  init() {
  }

  /**
   * Called when the component is destroyed, i.e. after a redraw where it is no
   * longer a part of the view.
   *
   * @see https://lhorie.github.io/mithril/mithril.component.html#unloading-components
   * @param {Object} e
   * @public
   */
  onunload() {
  }

  /**
   * Get the renderable virtual DOM that represents the component's view.
   *
   * This should NOT be overridden by subclasses. Subclasses wishing to define
   * their virtual DOM should override Component#view instead.
   *
   * @example
   * this.myComponentInstance = new MyComponent({foo: 'bar'});
   * return m('div', this.myComponentInstance.render());
   *
   * @returns {Object}
   * @final
   * @public
   */
  render() {
    const vdom = this.retain ? {subtree: 'retain'} : this.view();

    // Override the root element's config attribute with our own function, which
    // will set the component instance's element property to the root DOM
    // element, and then run the component class' config method.
    vdom.attrs = vdom.attrs || {};

    const originalConfig = vdom.attrs.config;

    vdom.attrs.config = (...args) => {
      this.element = args[0];
      this.config.apply(this, args.slice(1));
      if (originalConfig) originalConfig.apply(this, args);
    };

    return vdom;
  }

  /**
   * Returns a jQuery object for this component's element. If you pass in a
   * selector string, this method will return a jQuery object, using the current
   * element as its buffer.
   *
   * For example, calling `component.$('li')` will return a jQuery object
   * containing all of the `li` elements inside the DOM element of this
   * component.
   *
   * @param {String} [selector] a jQuery-compatible selector string
   * @returns {jQuery} the jQuery object for the DOM node
   * @final
   * @public
   */
  $(selector) {
    const $element = $(this.element);

    return selector ? $element.find(selector) : $element;
  }

  /**
   * Called after the component's root element is redrawn. This hook can be used
   * to perform any actions on the DOM, both on the initial draw and any
   * subsequent redraws. See Mithril's documentation for more information.
   *
   * @see https://lhorie.github.io/mithril/mithril.html#the-config-attribute
   * @param {Boolean} isInitialized
   * @param {Object} context
   * @param {Object} vdom
   * @public
   */
  config() {
  }

  /**
   * Get the virtual DOM that represents the component's view.
   *
   * @return {Object} The virtual DOM
   * @protected
   */
  view() {
    throw new Error('Component#view must be implemented by subclass');
  }

  /**
   * Get a Mithril component object for this component, preloaded with props.
   *
   * @see https://lhorie.github.io/mithril/mithril.component.html
   * @param {Object} [props] Properties to set on the component
   * @param children
   * @return {Object} The Mithril component object
   * @property {function} controller
   * @property {function} view
   * @property {Object} component The class of this component
   * @property {Object} props The props that were passed to the component
   * @public
   */
  static component(props = {}, children = null) {
    const componentProps = Object.assign({}, props);

    if (children) componentProps.children = children;

    this.initProps(componentProps);

    // Set up a function for Mithril to get the component's view. It will accept
    // the component's controller (which happens to be the component itself, in
    // our case), update its props with the ones supplied, and then render the view.
    const view = (component) => {
      component.props = componentProps;
      return component.render();
    };

    // Mithril uses this property on the view function to cache component
    // controllers between redraws, thus persisting component state.
    view.$original = this.prototype.view;

    // Our output object consists of a controller constructor + a view function
    // which Mithril will use to instantiate and render the component. We also
    // attach a reference to the props that were passed through and the
    // component's class for reference.
    const output = {
      controller: this.bind(undefined, componentProps),
      view: view,
      props: componentProps,
      component: this
    };

    // If a `key` prop was set, then we'll assume that we want that to actually
    // show up as an attribute on the component object so that Mithril's key
    // algorithm can be applied.
    if (componentProps.key) {
      output.attrs = {key: componentProps.key};
    }

    return output;
  }

  /**
   * Initialize the component's props.
   *
   * @param {Object} props
   * @public
   */
  static initProps(props) {
  }
}
