import Component from 'flarum/Component';
import Button from 'flarum/components/Button';
import LinkButton from 'flarum/components/LinkButton';

/**
 * The `Navigation` component displays a set of navigation buttons. Typically
 * this is just a back button which pops the app's History. If the user is on
 * the root page and there is no history to pop, then in some instances it may
 * show a button that toggles the app's drawer.
 *
 * If the app has a pane, it will also include a 'pin' button which toggles the
 * pinned state of the pane.
 *
 * Accepts the following props:
 *
 * - `className` The name of a class to set on the root element.
 * - `drawer` Whether or not to show a button to toggle the app's drawer if
 *   there is no more history to pop.
 */
export default class Navigation extends Component {
  view() {
    const {history, pane} = app;

    return (
      <div className={'Navigation ButtonGroup ' + (this.props.className || '')}
        onmouseenter={pane && pane.show.bind(pane)}
        onmouseleave={pane && pane.onmouseleave.bind(pane)}>
        {history.canGoBack()
          ? [this.getBackButton(), this.getPaneButton()]
          : this.getDrawerButton()}
      </div>
    );
  }

  config(isInitialized, context) {
    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;
  }

  /**
   * Get the back button.
   *
   * @return {Object}
   * @protected
   */
  getBackButton() {
    const {history} = app;
    const previous = history.getPrevious() || {};

    return LinkButton.component({
      className: 'Button Navigation-back Button--icon',
      href: history.backUrl(),
      icon: 'chevron-left',
      title: previous.title,
      config: () => {},
      onclick: e => {
        if (e.shiftKey || e.ctrlKey || e.metaKey || e.which === 2) return;
        e.preventDefault();
        history.back();
      }
    });
  }

  /**
   * Get the pane pinned toggle button.
   *
   * @return {Object|String}
   * @protected
   */
  getPaneButton() {
    const {pane} = app;

    if (!pane || !pane.active) return '';

    return Button.component({
      className: 'Button Button--icon Navigation-pin' + (pane.pinned ? ' active' : ''),
      onclick: pane.togglePinned.bind(pane),
      icon: 'thumb-tack'
    });
  }

  /**
   * Get the drawer toggle button.
   *
   * @return {Object|String}
   * @protected
   */
  getDrawerButton() {
    if (!this.props.drawer) return '';

    const {drawer} = app;
    const user = app.session.user;

    return Button.component({
      className: 'Button Button--icon Navigation-drawer' +
        (user && user.newNotificationsCount() ? ' new' : ''),
      onclick: e => {
        e.stopPropagation();
        drawer.show();
      },
      icon: 'reorder'
    });
  }
}
