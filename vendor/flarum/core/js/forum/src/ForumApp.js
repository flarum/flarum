import History from 'flarum/utils/History';
import App from 'flarum/App';
import Search from 'flarum/components/Search';
import Composer from 'flarum/components/Composer';
import ReplyComposer from 'flarum/components/ReplyComposer';
import DiscussionPage from 'flarum/components/DiscussionPage';
import SignUpModal from 'flarum/components/SignUpModal';

export default class ForumApp extends App {
  constructor(...args) {
    super(...args);

    /**
     * The app's history stack, which keeps track of which routes the user visits
     * so that they can easily navigate back to the previous route.
     *
     * @type {History}
     */
    this.history = new History();

    /**
     * An object which controls the state of the page's side pane.
     *
     * @type {Pane}
     */
    this.pane = null;

    /**
     * The page's search component instance.
     *
     * @type {SearchBox}
     */
    this.search = new Search();

    /**
     * An object which controls the state of the page's drawer.
     *
     * @type {Drawer}
     */
    this.drawer = null;

    /**
     * A map of post types to their components.
     *
     * @type {Object}
     */
    this.postComponents = {};

    /**
     * A map of notification types to their components.
     *
     * @type {Object}
     */
    this.notificationComponents = {};
  }

  /**
   * Check whether or not the user is currently composing a reply to a
   * discussion.
   *
   * @param {Discussion} discussion
   * @return {Boolean}
   */
  composingReplyTo(discussion) {
    return this.composer.component instanceof ReplyComposer &&
      this.composer.component.props.discussion === discussion &&
      this.composer.position !== Composer.PositionEnum.HIDDEN;
  }

  /**
   * Check whether or not the user is currently viewing a discussion.
   *
   * @param {Discussion} discussion
   * @return {Boolean}
   */
  viewingDiscussion(discussion) {
    return this.current instanceof DiscussionPage &&
      this.current.discussion === discussion;
  }

  /**
   * Callback for when an external authenticator (social login) action has
   * completed.
   *
   * If the payload indicates that the user has been logged in, then the page
   * will be reloaded. Otherwise, a SignUpModal will be opened, prefilled
   * with the provided details.
   *
   * @param {Object} payload A dictionary of props to pass into the sign up
   *     modal. A truthy `authenticated` prop indicates that the user has logged
   *     in, and thus the page is reloaded.
   * @public
   */
  authenticationComplete(payload) {
    if (payload.authenticated) {
      window.location.reload();
    } else {
      const modal = new SignUpModal(payload);
      this.modal.show(modal);
      modal.$('[name=password]').focus();
    }
  }
}
