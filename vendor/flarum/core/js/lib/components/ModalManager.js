import Component from 'flarum/Component';
import Modal from 'flarum/components/Modal';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
  init() {
    this.showing = false;
    this.component = null;
  }

  view() {
    return (
      <div className="ModalManager modal fade">
        {this.component && this.component.render()}
      </div>
    );
  }

  config(isInitialized, context) {
    if (isInitialized) return;

    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;

    this.$()
      .on('hidden.bs.modal', this.clear.bind(this))
      .on('shown.bs.modal', this.onready.bind(this));
  }

  /**
   * Show a modal dialog.
   *
   * @param {Modal} component
   * @public
   */
  show(component) {
    if (!(component instanceof Modal)) {
      throw new Error('The ModalManager component can only show Modal components');
    }

    clearTimeout(this.hideTimeout);

    this.showing = true;
    this.component = component;

    app.current.retain = true;

    m.redraw(true);

    this.$().modal({backdrop: this.component.isDismissible() ? true : 'static'}).modal('show');
    this.onready();
  }

  /**
   * Close the modal dialog.
   *
   * @public
   */
  close() {
    if (!this.showing) return;

    // Don't hide the modal immediately, because if the consumer happens to call
    // the `show` method straight after to show another modal dialog, it will
    // cause Bootstrap's modal JS to misbehave. Instead we will wait for a tiny
    // bit to give the `show` method the opportunity to prevent this from going
    // ahead.
    this.hideTimeout = setTimeout(() => {
      this.$().modal('hide');
      this.showing = false;
    });
  }

  /**
   * Clear content from the modal area.
   *
   * @protected
   */
  clear() {
    if (this.component) {
      this.component.onhide();
    }

    this.component = null;

    app.current.retain = false;

    m.lazyRedraw();
  }

  /**
   * When the modal dialog is ready to be used, tell it!
   *
   * @protected
   */
  onready() {
    if (this.component && this.component.onready) {
      this.component.onready(this.$());
    }
  }
}
