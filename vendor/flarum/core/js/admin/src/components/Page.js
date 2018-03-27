import Component from 'flarum/Component';

/**
 * The `Page` component
 *
 * @abstract
 */
export default class Page extends Component {
  init() {
    app.previous = app.current;
    app.current = this;

    app.modal.close();

    /**
     * A class name to apply to the body while the route is active.
     *
     * @type {String}
     */
    this.bodyClass = '';
  }

  config(isInitialized, context) {
    if (isInitialized) return;

    if (this.bodyClass) {
      $('#app').addClass(this.bodyClass);

      context.onunload = () => $('#app').removeClass(this.bodyClass);
    }
  }
}
