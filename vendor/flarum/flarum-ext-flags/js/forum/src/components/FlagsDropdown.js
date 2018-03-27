import NotificationsDropdown from 'flarum/components/NotificationsDropdown';

import FlagList from 'flarum/flags/components/FlagList';

export default class FlagsDropdown extends NotificationsDropdown {
  static initProps(props) {
    props.label = props.label || app.translator.trans('flarum-flags.forum.flagged_posts.tooltip');
    props.icon = props.icon || 'flag';

    super.initProps(props);
  }

  init() {
    super.init();

    this.list = new FlagList();
  }

  goToRoute() {
    m.route(app.route('flags'));
  }

  getUnreadCount() {
    return app.cache.flags ? app.cache.flags.length : app.forum.attribute('flagsCount');
  }

  getNewCount() {
    return app.session.user.attribute('newFlagsCount');
  }
}
