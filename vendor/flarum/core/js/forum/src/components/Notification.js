import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/helpers/humanTime';

/**
 * The `Notification` component abstract displays a single notification.
 * Subclasses should implement the `icon`, `href`, and `content` methods.
 *
 * ### Props
 *
 * - `notification`
 *
 * @abstract
 */
export default class Notification extends Component {
  view() {
    const notification = this.props.notification;
    const href = this.href();

    return (
      <a className={'Notification Notification--' + notification.contentType() + ' ' + (!notification.isRead() ? 'unread' : '')}
        href={href}
        config={function(element, isInitialized) {
          if (href.indexOf('://') === -1) m.route.apply(this, arguments);

          if (!isInitialized) $(element).click(this.markAsRead.bind(this));
        }}>
        {avatar(notification.sender())}
        {icon(this.icon(), {className: 'Notification-icon'})}
        <span className="Notification-content">{this.content()}</span>
        {humanTime(notification.time())}
        <div className="Notification-excerpt">
          {this.excerpt()}
        </div>
      </a>
    );
  }

  /**
   * Get the name of the icon that should be displayed in the notification.
   *
   * @return {String}
   * @abstract
   */
  icon() {
  }

  /**
   * Get the URL that the notification should link to.
   *
   * @return {String}
   * @abstract
   */
  href() {
  }

  /**
   * Get the content of the notification.
   *
   * @return {VirtualElement}
   * @abstract
   */
  content() {
  }

  /**
   * Get the excerpt of the notification.
   *
   * @return {VirtualElement}
   * @abstract
   */
  excerpt() {
  }

  /**
   * Mark the notification as read.
   */
  markAsRead() {
    if (this.props.notification.isRead()) return;

    app.session.user.pushAttributes({unreadNotificationsCount: app.session.user.unreadNotificationsCount() - 1});

    this.props.notification.save({isRead: true});
  }
}
