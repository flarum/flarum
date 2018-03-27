import Component from 'flarum/Component';
import listItems from 'flarum/helpers/listItems';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import Discussion from 'flarum/models/Discussion';

/**
 * The `NotificationList` component displays a list of the logged-in user's
 * notifications, grouped by discussion.
 */
export default class NotificationList extends Component {
  init() {
    /**
     * Whether or not the notifications are loading.
     *
     * @type {Boolean}
     */
    this.loading = false;
  }

  view() {
    const groups = [];

    if (app.cache.notifications) {
      const discussions = {};

      // Build an array of discussions which the notifications are related to,
      // and add the notifications as children.
      app.cache.notifications.forEach(notification => {
        const subject = notification.subject();

        if (typeof subject === 'undefined') return;

        // Get the discussion that this notification is related to. If it's not
        // directly related to a discussion, it may be related to a post or
        // other entity which is related to a discussion.
        let discussion = false;
        if (subject instanceof Discussion) discussion = subject;
        else if (subject && subject.discussion) discussion = subject.discussion();

        // If the notification is not related to a discussion directly or
        // indirectly, then we will assign it to a neutral group.
        const key = discussion ? discussion.id() : 0;
        discussions[key] = discussions[key] || {discussion: discussion, notifications: []};
        discussions[key].notifications.push(notification);

        if (groups.indexOf(discussions[key]) === -1) {
          groups.push(discussions[key]);
        }
      });
    }

    return (
      <div className="NotificationList">
        <div className="NotificationList-header">
          <div className="App-primaryControl">
            {Button.component({
              className: 'Button Button--icon Button--link',
              icon: 'check',
              title: app.translator.trans('core.forum.notifications.mark_all_as_read_tooltip'),
              onclick: this.markAllAsRead.bind(this)
            })}
          </div>

          <h4 className="App-titleControl App-titleControl--text">{app.translator.trans('core.forum.notifications.title')}</h4>
        </div>

        <div className="NotificationList-content">
          {groups.length
            ? groups.map(group => {
              const badges = group.discussion && group.discussion.badges().toArray();

              return (
                <div className="NotificationGroup">
                  {group.discussion
                    ? (
                      <a className="NotificationGroup-header"
                        href={app.route.discussion(group.discussion)}
                        config={m.route}>
                        {badges && badges.length ? <ul className="NotificationGroup-badges badges">{listItems(badges)}</ul> : ''}
                        {group.discussion.title()}
                      </a>
                    ) : (
                      <div className="NotificationGroup-header">
                        {app.forum.attribute('title')}
                      </div>
                    )}

                  <ul className="NotificationGroup-content">
                    {group.notifications.map(notification => {
                      const NotificationComponent = app.notificationComponents[notification.contentType()];
                      return NotificationComponent ? <li>{NotificationComponent.component({notification})}</li> : '';
                    })}
                  </ul>
                </div>
              );
            })
            : !this.loading
              ? <div className="NotificationList-empty">{app.translator.trans('core.forum.notifications.empty_text')}</div>
              : LoadingIndicator.component({className: 'LoadingIndicator--block'})}
        </div>
      </div>
    );
  }

  /**
   * Load notifications into the application's cache if they haven't already
   * been loaded.
   */
  load() {
    if (app.cache.notifications && !app.session.user.newNotificationsCount()) {
      return;
    }

    this.loading = true;
    m.redraw();

    app.store.find('notifications')
      .then(notifications => {
        app.session.user.pushAttributes({newNotificationsCount: 0});
        app.cache.notifications = notifications.sort((a, b) => b.time() - a.time());
      })
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }

  /**
   * Mark all of the notifications as read.
   */
  markAllAsRead() {
    if (!app.cache.notifications) return;

    app.session.user.pushAttributes({unreadNotificationsCount: 0});

    app.cache.notifications.forEach(notification => notification.pushAttributes({isRead: true}));

    app.request({
      url: app.forum.attribute('apiUrl') + '/notifications/read',
      method: 'POST'
    });
  }
}
