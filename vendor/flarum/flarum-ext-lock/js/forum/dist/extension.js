'use strict';

System.register('flarum/lock/addLockBadge', ['flarum/extend', 'flarum/models/Discussion', 'flarum/components/Badge'], function (_export, _context) {
  "use strict";

  var extend, Discussion, Badge;
  function addLockBadge() {
    extend(Discussion.prototype, 'badges', function (badges) {
      if (this.isLocked()) {
        badges.add('locked', Badge.component({
          type: 'locked',
          label: app.translator.trans('flarum-lock.forum.badge.locked_tooltip'),
          icon: 'lock'
        }));
      }
    });
  }

  _export('default', addLockBadge);

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion.default;
    }, function (_flarumComponentsBadge) {
      Badge = _flarumComponentsBadge.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/lock/addLockControl', ['flarum/extend', 'flarum/utils/DiscussionControls', 'flarum/components/DiscussionPage', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var extend, DiscussionControls, DiscussionPage, Button;
  function addLockControl() {
    extend(DiscussionControls, 'moderationControls', function (items, discussion) {
      if (discussion.canLock()) {
        items.add('lock', Button.component({
          children: app.translator.trans(discussion.isLocked() ? 'flarum-lock.forum.discussion_controls.unlock_button' : 'flarum-lock.forum.discussion_controls.lock_button'),
          icon: 'lock',
          onclick: this.lockAction.bind(discussion)
        }));
      }
    });

    DiscussionControls.lockAction = function () {
      this.save({ isLocked: !this.isLocked() }).then(function () {
        if (app.current instanceof DiscussionPage) {
          app.current.stream.update();
        }

        m.redraw();
      });
    };
  }

  _export('default', addLockControl);

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls.default;
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/lock/components/DiscussionLockedNotification', ['flarum/components/Notification'], function (_export, _context) {
  "use strict";

  var Notification, DiscussionLockedNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification.default;
    }],
    execute: function () {
      DiscussionLockedNotification = function (_Notification) {
        babelHelpers.inherits(DiscussionLockedNotification, _Notification);

        function DiscussionLockedNotification() {
          babelHelpers.classCallCheck(this, DiscussionLockedNotification);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionLockedNotification.__proto__ || Object.getPrototypeOf(DiscussionLockedNotification)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionLockedNotification, [{
          key: 'icon',
          value: function icon() {
            return 'lock';
          }
        }, {
          key: 'href',
          value: function href() {
            var notification = this.props.notification;

            return app.route.discussion(notification.subject(), notification.content().postNumber);
          }
        }, {
          key: 'content',
          value: function content() {
            return app.translator.trans('flarum-lock.forum.notifications.discussion_locked_text', { user: this.props.notification.sender() });
          }
        }]);
        return DiscussionLockedNotification;
      }(Notification);

      _export('default', DiscussionLockedNotification);
    }
  };
});;
'use strict';

System.register('flarum/lock/components/DiscussionLockedPost', ['flarum/components/EventPost'], function (_export, _context) {
  "use strict";

  var EventPost, DiscussionLockedPost;
  return {
    setters: [function (_flarumComponentsEventPost) {
      EventPost = _flarumComponentsEventPost.default;
    }],
    execute: function () {
      DiscussionLockedPost = function (_EventPost) {
        babelHelpers.inherits(DiscussionLockedPost, _EventPost);

        function DiscussionLockedPost() {
          babelHelpers.classCallCheck(this, DiscussionLockedPost);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionLockedPost.__proto__ || Object.getPrototypeOf(DiscussionLockedPost)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionLockedPost, [{
          key: 'icon',
          value: function icon() {
            return this.props.post.content().locked ? 'lock' : 'unlock';
          }
        }, {
          key: 'descriptionKey',
          value: function descriptionKey() {
            return this.props.post.content().locked ? 'flarum-lock.forum.post_stream.discussion_locked_text' : 'flarum-lock.forum.post_stream.discussion_unlocked_text';
          }
        }]);
        return DiscussionLockedPost;
      }(EventPost);

      _export('default', DiscussionLockedPost);
    }
  };
});;
'use strict';

System.register('flarum/lock/main', ['flarum/extend', 'flarum/app', 'flarum/Model', 'flarum/models/Discussion', 'flarum/components/NotificationGrid', 'flarum/lock/components/DiscussionLockedPost', 'flarum/lock/components/DiscussionLockedNotification', 'flarum/lock/addLockBadge', 'flarum/lock/addLockControl'], function (_export, _context) {
  "use strict";

  var extend, app, Model, Discussion, NotificationGrid, DiscussionLockedPost, DiscussionLockedNotification, addLockBadge, addLockControl;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion.default;
    }, function (_flarumComponentsNotificationGrid) {
      NotificationGrid = _flarumComponentsNotificationGrid.default;
    }, function (_flarumLockComponentsDiscussionLockedPost) {
      DiscussionLockedPost = _flarumLockComponentsDiscussionLockedPost.default;
    }, function (_flarumLockComponentsDiscussionLockedNotification) {
      DiscussionLockedNotification = _flarumLockComponentsDiscussionLockedNotification.default;
    }, function (_flarumLockAddLockBadge) {
      addLockBadge = _flarumLockAddLockBadge.default;
    }, function (_flarumLockAddLockControl) {
      addLockControl = _flarumLockAddLockControl.default;
    }],
    execute: function () {

      app.initializers.add('flarum-lock', function () {
        app.postComponents.discussionLocked = DiscussionLockedPost;
        app.notificationComponents.discussionLocked = DiscussionLockedNotification;

        Discussion.prototype.isLocked = Model.attribute('isLocked');
        Discussion.prototype.canLock = Model.attribute('canLock');

        addLockBadge();
        addLockControl();

        extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
          items.add('discussionLocked', {
            name: 'discussionLocked',
            icon: 'lock',
            label: app.translator.trans('flarum-lock.forum.settings.notify_discussion_locked_label')
          });
        });
      });
    }
  };
});