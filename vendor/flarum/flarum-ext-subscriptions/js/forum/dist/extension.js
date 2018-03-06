'use strict';

System.register('flarum/subscriptions/addSubscriptionBadge', ['flarum/extend', 'flarum/models/Discussion', 'flarum/components/Badge'], function (_export, _context) {
  "use strict";

  var extend, Discussion, Badge;
  function addSubscriptionBadge() {
    extend(Discussion.prototype, 'badges', function (badges) {
      var badge = void 0;

      switch (this.subscription()) {
        case 'follow':
          badge = Badge.component({
            label: app.translator.trans('flarum-subscriptions.forum.badge.following_tooltip'),
            icon: 'star',
            type: 'following'
          });
          break;

        case 'ignore':
          badge = Badge.component({
            label: app.translator.trans('flarum-subscriptions.forum.badge.ignoring_tooltip'),
            icon: 'eye-slash',
            type: 'ignoring'
          });
          break;

        default:
        // no default
      }

      if (badge) {
        badges.add('subscription', badge);
      }
    });
  }

  _export('default', addSubscriptionBadge);

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

System.register('flarum/subscriptions/addSubscriptionControls', ['flarum/extend', 'flarum/components/Button', 'flarum/components/DiscussionPage', 'flarum/utils/DiscussionControls', 'flarum/subscriptions/components/SubscriptionMenu'], function (_export, _context) {
  "use strict";

  var extend, Button, DiscussionPage, DiscussionControls, SubscriptionMenu;
  function addSubscriptionControls() {
    extend(DiscussionControls, 'userControls', function (items, discussion, context) {
      if (app.session.user && !(context instanceof DiscussionPage)) {
        var states = {
          none: { label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.follow_button'), icon: 'star', save: 'follow' },
          follow: { label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.unfollow_button'), icon: 'star-o', save: false },
          ignore: { label: app.translator.trans('flarum-subscriptions.forum.discussion_controls.unignore_button'), icon: 'eye', save: false }
        };

        var subscription = discussion.subscription() || 'none';

        items.add('subscription', Button.component({
          children: states[subscription].label,
          icon: states[subscription].icon,
          onclick: discussion.save.bind(discussion, { subscription: states[subscription].save })
        }));
      }
    });

    extend(DiscussionPage.prototype, 'sidebarItems', function (items) {
      if (app.session.user) {
        var discussion = this.discussion;

        items.add('subscription', SubscriptionMenu.component({ discussion: discussion }));
      }
    });
  }

  _export('default', addSubscriptionControls);

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls.default;
    }, function (_flarumSubscriptionsComponentsSubscriptionMenu) {
      SubscriptionMenu = _flarumSubscriptionsComponentsSubscriptionMenu.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/subscriptions/addSubscriptionFilter', ['flarum/extend', 'flarum/components/LinkButton', 'flarum/components/IndexPage', 'flarum/components/DiscussionList'], function (_export, _context) {
  "use strict";

  var extend, LinkButton, IndexPage, DiscussionList;
  function addSubscriptionFilter() {
    extend(IndexPage.prototype, 'navItems', function (items) {
      if (app.session.user) {
        var params = this.stickyParams();

        params.filter = 'following';

        items.add('following', LinkButton.component({
          href: app.route('index.filter', params),
          children: app.translator.trans('flarum-subscriptions.forum.index.following_link'),
          icon: 'star'
        }), 50);
      }
    });

    extend(DiscussionList.prototype, 'requestParams', function (params) {
      if (this.props.params.filter === 'following') {
        params.filter.q = (params.filter.q || '') + ' is:following';
      }
    });
  }

  _export('default', addSubscriptionFilter);

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsLinkButton) {
      LinkButton = _flarumComponentsLinkButton.default;
    }, function (_flarumComponentsIndexPage) {
      IndexPage = _flarumComponentsIndexPage.default;
    }, function (_flarumComponentsDiscussionList) {
      DiscussionList = _flarumComponentsDiscussionList.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/subscriptions/addSubscriptionSettings', ['flarum/extend', 'flarum/components/SettingsPage', 'flarum/components/FieldSet', 'flarum/components/Switch', 'flarum/utils/ItemList'], function (_export, _context) {
  "use strict";

  var extend, SettingsPage, FieldSet, Switch, ItemList;

  _export('default', function () {
    extend(SettingsPage.prototype, 'notificationsItems', function (items) {
      items.add('followAfterReply', Switch.component({
        children: app.translator.trans('flarum-subscriptions.forum.settings.follow_after_reply_label'),
        state: this.user.preferences().followAfterReply,
        onchange: this.preferenceSaver('followAfterReply')
      }));
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsSettingsPage) {
      SettingsPage = _flarumComponentsSettingsPage.default;
    }, function (_flarumComponentsFieldSet) {
      FieldSet = _flarumComponentsFieldSet.default;
    }, function (_flarumComponentsSwitch) {
      Switch = _flarumComponentsSwitch.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/subscriptions/components/NewPostNotification', ['flarum/components/Notification', 'flarum/helpers/username'], function (_export, _context) {
  "use strict";

  var Notification, username, NewPostNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }],
    execute: function () {
      NewPostNotification = function (_Notification) {
        babelHelpers.inherits(NewPostNotification, _Notification);

        function NewPostNotification() {
          babelHelpers.classCallCheck(this, NewPostNotification);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(NewPostNotification).apply(this, arguments));
        }

        babelHelpers.createClass(NewPostNotification, [{
          key: 'icon',
          value: function icon() {
            return 'star';
          }
        }, {
          key: 'href',
          value: function href() {
            var notification = this.props.notification;
            var discussion = notification.subject();
            var content = notification.content() || {};

            return app.route.discussion(discussion, content.postNumber);
          }
        }, {
          key: 'content',
          value: function content() {
            return app.translator.trans('flarum-subscriptions.forum.notifications.new_post_text', { user: this.props.notification.sender() });
          }
        }]);
        return NewPostNotification;
      }(Notification);

      _export('default', NewPostNotification);
    }
  };
});;
'use strict';

System.register('flarum/subscriptions/components/SubscriptionMenu', ['flarum/components/Dropdown', 'flarum/components/Button', 'flarum/helpers/icon', 'flarum/utils/extractText', 'flarum/subscriptions/components/SubscriptionMenuItem'], function (_export, _context) {
  "use strict";

  var Dropdown, Button, icon, extractText, SubscriptionMenuItem, SubscriptionMenu;
  return {
    setters: [function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }, function (_flarumSubscriptionsComponentsSubscriptionMenuItem) {
      SubscriptionMenuItem = _flarumSubscriptionsComponentsSubscriptionMenuItem.default;
    }],
    execute: function () {
      SubscriptionMenu = function (_Dropdown) {
        babelHelpers.inherits(SubscriptionMenu, _Dropdown);

        function SubscriptionMenu() {
          babelHelpers.classCallCheck(this, SubscriptionMenu);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(SubscriptionMenu).apply(this, arguments));
        }

        babelHelpers.createClass(SubscriptionMenu, [{
          key: 'init',
          value: function init() {
            this.options = [{
              subscription: false,
              icon: 'star-o',
              label: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_button'),
              description: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_text')
            }, {
              subscription: 'follow',
              icon: 'star',
              label: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button'),
              description: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_text')
            }, {
              subscription: 'ignore',
              icon: 'eye-slash',
              label: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button'),
              description: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_text')
            }];
          }
        }, {
          key: 'view',
          value: function view() {
            var _this2 = this;

            var discussion = this.props.discussion;
            var subscription = discussion.subscription();

            var buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.follow_button');
            var buttonIcon = 'star-o';
            var buttonClass = 'SubscriptionMenu-button--' + subscription;

            switch (subscription) {
              case 'follow':
                buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button');
                buttonIcon = 'star';
                break;

              case 'ignore':
                buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button');
                buttonIcon = 'eye-slash';
                break;

              default:
              // no default
            }

            var preferences = app.session.user.preferences();
            var notifyEmail = preferences['notify_newPost_email'];
            var notifyAlert = preferences['notify_newPost_alert'];
            var title = extractText(app.translator.trans(notifyEmail ? 'flarum-subscriptions.forum.sub_controls.notify_email_tooltip' : 'flarum-subscriptions.forum.sub_controls.notify_alert_tooltip'));

            var buttonProps = {
              className: 'Button SubscriptionMenu-button ' + buttonClass,
              icon: buttonIcon,
              children: buttonLabel,
              onclick: this.saveSubscription.bind(this, discussion, ['follow', 'ignore'].indexOf(subscription) !== -1 ? false : 'follow'),
              title: title
            };

            if ((notifyEmail || notifyAlert) && subscription === false) {
              buttonProps.config = function (element) {
                $(element).tooltip({
                  container: '.SubscriptionMenu',
                  placement: 'bottom',
                  delay: 250,
                  title: title
                });
              };
            } else {
              buttonProps.config = function (element) {
                return $(element).tooltip('destroy');
              };
            }

            return m(
              'div',
              { className: 'Dropdown ButtonGroup SubscriptionMenu' },
              Button.component(buttonProps),
              m(
                'button',
                { className: 'Dropdown-toggle Button Button--icon ' + buttonClass, 'data-toggle': 'dropdown' },
                icon('caret-down', { className: 'Button-icon' })
              ),
              m(
                'ul',
                { className: 'Dropdown-menu dropdown-menu Dropdown-menu--right' },
                this.options.map(function (props) {
                  props.onclick = _this2.saveSubscription.bind(_this2, discussion, props.subscription);
                  props.active = subscription === props.subscription;

                  return m(
                    'li',
                    null,
                    SubscriptionMenuItem.component(props)
                  );
                })
              )
            );
          }
        }, {
          key: 'saveSubscription',
          value: function saveSubscription(discussion, subscription) {
            discussion.save({ subscription: subscription });

            this.$('.SubscriptionMenu-button').tooltip('hide');
          }
        }]);
        return SubscriptionMenu;
      }(Dropdown);

      _export('default', SubscriptionMenu);
    }
  };
});;
'use strict';

System.register('flarum/subscriptions/components/SubscriptionMenuItem', ['flarum/Component', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Component, icon, SubscriptionMenuItem;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      SubscriptionMenuItem = function (_Component) {
        babelHelpers.inherits(SubscriptionMenuItem, _Component);

        function SubscriptionMenuItem() {
          babelHelpers.classCallCheck(this, SubscriptionMenuItem);
          return babelHelpers.possibleConstructorReturn(this, Object.getPrototypeOf(SubscriptionMenuItem).apply(this, arguments));
        }

        babelHelpers.createClass(SubscriptionMenuItem, [{
          key: 'view',
          value: function view() {
            return m(
              'button',
              { className: 'SubscriptionMenuItem hasIcon', onclick: this.props.onclick },
              this.props.active ? icon('check', { className: 'Button-icon' }) : '',
              m(
                'span',
                { className: 'SubscriptionMenuItem-label' },
                icon(this.props.icon, { className: 'Button-icon' }),
                m(
                  'strong',
                  null,
                  this.props.label
                ),
                m(
                  'span',
                  { className: 'SubscriptionMenuItem-description' },
                  this.props.description
                )
              )
            );
          }
        }]);
        return SubscriptionMenuItem;
      }(Component);

      _export('default', SubscriptionMenuItem);
    }
  };
});;
'use strict';

System.register('flarum/subscriptions/main', ['flarum/extend', 'flarum/app', 'flarum/Model', 'flarum/models/Discussion', 'flarum/components/NotificationGrid', 'flarum/subscriptions/addSubscriptionBadge', 'flarum/subscriptions/addSubscriptionControls', 'flarum/subscriptions/addSubscriptionFilter', 'flarum/subscriptions/addSubscriptionSettings', 'flarum/subscriptions/components/NewPostNotification'], function (_export, _context) {
  "use strict";

  var extend, app, Model, Discussion, NotificationGrid, addSubscriptionBadge, addSubscriptionControls, addSubscriptionFilter, addSubscriptionSettings, NewPostNotification;
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
    }, function (_flarumSubscriptionsAddSubscriptionBadge) {
      addSubscriptionBadge = _flarumSubscriptionsAddSubscriptionBadge.default;
    }, function (_flarumSubscriptionsAddSubscriptionControls) {
      addSubscriptionControls = _flarumSubscriptionsAddSubscriptionControls.default;
    }, function (_flarumSubscriptionsAddSubscriptionFilter) {
      addSubscriptionFilter = _flarumSubscriptionsAddSubscriptionFilter.default;
    }, function (_flarumSubscriptionsAddSubscriptionSettings) {
      addSubscriptionSettings = _flarumSubscriptionsAddSubscriptionSettings.default;
    }, function (_flarumSubscriptionsComponentsNewPostNotification) {
      NewPostNotification = _flarumSubscriptionsComponentsNewPostNotification.default;
    }],
    execute: function () {

      app.initializers.add('subscriptions', function () {
        app.notificationComponents.newPost = NewPostNotification;

        Discussion.prototype.subscription = Model.attribute('subscription');

        addSubscriptionBadge();
        addSubscriptionControls();
        addSubscriptionFilter();
        addSubscriptionSettings();

        extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
          items.add('newPost', {
            name: 'newPost',
            icon: 'star',
            label: app.translator.trans('flarum-subscriptions.forum.settings.notify_new_post_label')
          });
        });
      });
    }
  };
});