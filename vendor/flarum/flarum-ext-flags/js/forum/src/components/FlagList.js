import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/helpers/humanTime';

export default class FlagList extends Component {
  init() {
    /**
     * Whether or not the notifications are loading.
     *
     * @type {Boolean}
     */
    this.loading = false;
  }

  view() {
    const flags = app.cache.flags || [];

    return (
      <div className="NotificationList FlagList">
        <div className="NotificationList-header">
          <h4 className="App-titleControl App-titleControl--text">{app.translator.trans('flarum-flags.forum.flagged_posts.title')}</h4>
        </div>
        <div className="NotificationList-content">
          <ul className="NotificationGroup-content">
            {flags.length
              ? flags.map(flag => {
                const post = flag.post();

                return (
                  <li>
                    <a href={app.route.post(post)} className="Notification Flag" config={function(element, isInitialized) {
                      m.route.apply(this, arguments);

                      if (!isInitialized) $(element).on('click', () => app.cache.flagIndex = post);
                    }}>
                      {avatar(post.user())}
                      {icon('flag', {className: 'Notification-icon'})}
                      <span className="Notification-content">
                        {app.translator.trans('flarum-flags.forum.flagged_posts.item_text', {username: username(post.user()), em: <em/>, discussion: post.discussion().title()})}
                      </span>
                      {humanTime(flag.time())}
                      <div className="Notification-excerpt">
                        {post.contentPlain()}
                      </div>
                    </a>
                  </li>
                );
              })
              : !this.loading
                ? <div className="NotificationList-empty">{app.translator.trans('flarum-flags.forum.flagged_posts.empty_text')}</div>
                : LoadingIndicator.component({className: 'LoadingIndicator--block'})}
          </ul>
        </div>
      </div>
    );
  }

  /**
   * Load flags into the application's cache if they haven't already
   * been loaded.
   */
  load() {
    if (app.cache.flags && !app.session.user.attribute('newFlagsCount')) {
      return;
    }

    this.loading = true;
    m.redraw();

    app.store.find('flags')
      .then(flags => {
        app.session.user.pushAttributes({newFlagsCount: 0});
        app.cache.flags = flags.sort((a, b) => b.time() - a.time());
      })
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
