'use strict';

System.register('flarum/flags/addFlagControl', ['flarum/extend', 'flarum/app', 'flarum/utils/PostControls', 'flarum/components/Button', 'flarum/flags/components/FlagPostModal'], function (_export, _context) {
  "use strict";

  var extend, app, PostControls, Button, FlagPostModal;

  _export('default', function () {
    extend(PostControls, 'userControls', function (items, post) {
      if (post.isHidden() || post.contentType() !== 'comment' || !post.canFlag() || post.user() === app.session.user) return;

      items.add('flag', m(
        Button,
        { icon: 'flag', onclick: function onclick() {
            return app.modal.show(new FlagPostModal({ post: post }));
          } },
        app.translator.trans('flarum-flags.forum.post_controls.flag_button')
      ));
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumUtilsPostControls) {
      PostControls = _flarumUtilsPostControls.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumFlagsComponentsFlagPostModal) {
      FlagPostModal = _flarumFlagsComponentsFlagPostModal.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/flags/addFlagsDropdown', ['flarum/extend', 'flarum/app', 'flarum/components/HeaderSecondary', 'flarum/flags/components/FlagsDropdown'], function (_export, _context) {
  "use strict";

  var extend, app, HeaderSecondary, FlagsDropdown;

  _export('default', function () {
    extend(HeaderSecondary.prototype, 'items', function (items) {
      if (app.forum.attribute('canViewFlags')) {
        items.add('flags', m(FlagsDropdown, null), 15);
      }
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsHeaderSecondary) {
      HeaderSecondary = _flarumComponentsHeaderSecondary.default;
    }, function (_flarumFlagsComponentsFlagsDropdown) {
      FlagsDropdown = _flarumFlagsComponentsFlagsDropdown.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/flags/addFlagsToPosts', ['flarum/extend', 'flarum/app', 'flarum/components/Post', 'flarum/components/Button', 'flarum/utils/ItemList', 'flarum/utils/PostControls'], function (_export, _context) {
  "use strict";

  var extend, app, Post, Button, ItemList, PostControls;

  _export('default', function () {
    extend(Post.prototype, 'attrs', function (attrs) {
      if (this.props.post.flags().length) {
        attrs.className += ' Post--flagged';
      }
    });

    Post.prototype.dismissFlag = function (data) {
      var post = this.props.post;

      delete post.data.relationships.flags;

      this.subtree.invalidate();

      if (app.cache.flags) {
        app.cache.flags.some(function (flag, i) {
          if (flag.post() === post) {
            app.cache.flags.splice(i, 1);

            if (app.cache.flagIndex === post) {
              var next = app.cache.flags[i];

              if (!next) next = app.cache.flags[0];

              if (next) {
                var nextPost = next.post();
                app.cache.flagIndex = nextPost;
                m.route(app.route.post(nextPost));
              }
            }

            return true;
          }
        });
      }

      return app.request({
        url: app.forum.attribute('apiUrl') + post.apiEndpoint() + '/flags',
        method: 'DELETE',
        data: data
      });
    };

    Post.prototype.flagActionItems = function () {
      var _this = this;

      var items = new ItemList();

      var controls = PostControls.destructiveControls(this.props.post);

      Object.keys(controls.items).forEach(function (k) {
        var props = controls.get(k).props;

        props.className = 'Button';

        extend(props, 'onclick', function () {
          return _this.dismissFlag();
        });
      });

      items.add('controls', m(
        'div',
        { className: 'ButtonGroup' },
        controls.toArray()
      ));

      items.add('dismiss', m(
        Button,
        { className: 'Button', icon: 'eye-slash', onclick: this.dismissFlag.bind(this) },
        app.translator.trans('flarum-flags.forum.post.dismiss_flag_button')
      ), -100);

      return items;
    };

    extend(Post.prototype, 'content', function (vdom) {
      var _this2 = this;

      var post = this.props.post;
      var flags = post.flags();

      if (!flags.length) return;

      if (post.isHidden()) this.revealContent = true;

      vdom.unshift(m(
        'div',
        { className: 'Post-flagged' },
        m(
          'div',
          { className: 'Post-flagged-flags' },
          flags.map(function (flag) {
            return m(
              'div',
              { className: 'Post-flagged-flag' },
              _this2.flagReason(flag)
            );
          })
        ),
        m(
          'div',
          { className: 'Post-flagged-actions' },
          this.flagActionItems().toArray()
        )
      ));
    });

    Post.prototype.flagReason = function (flag) {
      if (flag.type() === 'user') {
        var user = flag.user();
        var reason = flag.reason();
        var detail = flag.reasonDetail();

        return [app.translator.trans(reason ? 'flarum-flags.forum.post.flagged_by_with_reason_text' : 'flarum-flags.forum.post.flagged_by_text', { user: user, reason: reason }), detail ? m(
          'span',
          { className: 'Post-flagged-detail' },
          detail
        ) : ''];
      }
    };
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumComponentsPost) {
      Post = _flarumComponentsPost.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumUtilsPostControls) {
      PostControls = _flarumUtilsPostControls.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/flags/components/FlagList', ['flarum/Component', 'flarum/components/LoadingIndicator', 'flarum/helpers/avatar', 'flarum/helpers/username', 'flarum/helpers/icon', 'flarum/helpers/humanTime'], function (_export, _context) {
  "use strict";

  var Component, LoadingIndicator, avatar, username, icon, humanTime, FlagList;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumHelpersHumanTime) {
      humanTime = _flarumHelpersHumanTime.default;
    }],
    execute: function () {
      FlagList = function (_Component) {
        babelHelpers.inherits(FlagList, _Component);

        function FlagList() {
          babelHelpers.classCallCheck(this, FlagList);
          return babelHelpers.possibleConstructorReturn(this, (FlagList.__proto__ || Object.getPrototypeOf(FlagList)).apply(this, arguments));
        }

        babelHelpers.createClass(FlagList, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not the notifications are loading.
             *
             * @type {Boolean}
             */
            this.loading = false;
          }
        }, {
          key: 'view',
          value: function view() {
            var flags = app.cache.flags || [];

            return m(
              'div',
              { className: 'NotificationList FlagList' },
              m(
                'div',
                { className: 'NotificationList-header' },
                m(
                  'h4',
                  { className: 'App-titleControl App-titleControl--text' },
                  app.translator.trans('flarum-flags.forum.flagged_posts.title')
                )
              ),
              m(
                'div',
                { className: 'NotificationList-content' },
                m(
                  'ul',
                  { className: 'NotificationGroup-content' },
                  flags.length ? flags.map(function (flag) {
                    var post = flag.post();

                    return m(
                      'li',
                      null,
                      m(
                        'a',
                        { href: app.route.post(post), className: 'Notification Flag', config: function config(element, isInitialized) {
                            m.route.apply(this, arguments);

                            if (!isInitialized) $(element).on('click', function () {
                              return app.cache.flagIndex = post;
                            });
                          } },
                        avatar(post.user()),
                        icon('flag', { className: 'Notification-icon' }),
                        m(
                          'span',
                          { className: 'Notification-content' },
                          app.translator.trans('flarum-flags.forum.flagged_posts.item_text', { username: username(post.user()), em: m('em', null), discussion: post.discussion().title() })
                        ),
                        humanTime(flag.time()),
                        m(
                          'div',
                          { className: 'Notification-excerpt' },
                          post.contentPlain()
                        )
                      )
                    );
                  }) : !this.loading ? m(
                    'div',
                    { className: 'NotificationList-empty' },
                    app.translator.trans('flarum-flags.forum.flagged_posts.empty_text')
                  ) : LoadingIndicator.component({ className: 'LoadingIndicator--block' })
                )
              )
            );
          }
        }, {
          key: 'load',
          value: function load() {
            var _this2 = this;

            if (app.cache.flags && !app.session.user.attribute('newFlagsCount')) {
              return;
            }

            this.loading = true;
            m.redraw();

            app.store.find('flags').then(function (flags) {
              app.session.user.pushAttributes({ newFlagsCount: 0 });
              app.cache.flags = flags.sort(function (a, b) {
                return b.time() - a.time();
              });
            }).catch(function () {}).then(function () {
              _this2.loading = false;
              m.redraw();
            });
          }
        }]);
        return FlagList;
      }(Component);

      _export('default', FlagList);
    }
  };
});;
'use strict';

System.register('flarum/flags/components/FlagPostModal', ['flarum/components/Modal', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Modal, Button, FlagPostModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      FlagPostModal = function (_Modal) {
        babelHelpers.inherits(FlagPostModal, _Modal);

        function FlagPostModal() {
          babelHelpers.classCallCheck(this, FlagPostModal);
          return babelHelpers.possibleConstructorReturn(this, (FlagPostModal.__proto__ || Object.getPrototypeOf(FlagPostModal)).apply(this, arguments));
        }

        babelHelpers.createClass(FlagPostModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(FlagPostModal.prototype.__proto__ || Object.getPrototypeOf(FlagPostModal.prototype), 'init', this).call(this);

            this.success = false;

            this.reason = m.prop('');
            this.reasonDetail = m.prop('');
          }
        }, {
          key: 'className',
          value: function className() {
            return 'FlagPostModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-flags.forum.flag_post.title');
          }
        }, {
          key: 'content',
          value: function content() {
            if (this.success) {
              return m(
                'div',
                { className: 'Modal-body' },
                m(
                  'div',
                  { className: 'Form Form--centered' },
                  m(
                    'p',
                    { className: 'helpText' },
                    app.translator.trans('flarum-flags.forum.flag_post.confirmation_message')
                  ),
                  m(
                    'div',
                    { className: 'Form-group' },
                    m(
                      Button,
                      { className: 'Button Button--primary Button--block', onclick: this.hide.bind(this) },
                      app.translator.trans('flarum-flags.forum.flag_post.dismiss_button')
                    )
                  )
                )
              );
            }

            var guidelinesUrl = app.forum.attribute('guidelinesUrl');

            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form Form--centered' },
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'div',
                    null,
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'radio', name: 'reason', checked: this.reason() === 'off_topic', value: 'off_topic', onclick: m.withAttr('value', this.reason) }),
                      m(
                        'strong',
                        null,
                        app.translator.trans('flarum-flags.forum.flag_post.reason_off_topic_label')
                      ),
                      app.translator.trans('flarum-flags.forum.flag_post.reason_off_topic_text')
                    ),
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'radio', name: 'reason', checked: this.reason() === 'inappropriate', value: 'inappropriate', onclick: m.withAttr('value', this.reason) }),
                      m(
                        'strong',
                        null,
                        app.translator.trans('flarum-flags.forum.flag_post.reason_inappropriate_label')
                      ),
                      app.translator.trans('flarum-flags.forum.flag_post.reason_inappropriate_text', {
                        a: guidelinesUrl ? m('a', { href: guidelinesUrl, target: '_blank' }) : undefined
                      })
                    ),
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'radio', name: 'reason', checked: this.reason() === 'spam', value: 'spam', onclick: m.withAttr('value', this.reason) }),
                      m(
                        'strong',
                        null,
                        app.translator.trans('flarum-flags.forum.flag_post.reason_spam_label')
                      ),
                      app.translator.trans('flarum-flags.forum.flag_post.reason_spam_text')
                    ),
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'radio', name: 'reason', checked: this.reason() === 'other', value: 'other', onclick: m.withAttr('value', this.reason) }),
                      m(
                        'strong',
                        null,
                        app.translator.trans('flarum-flags.forum.flag_post.reason_other_label')
                      ),
                      this.reason() === 'other' ? m('textarea', { className: 'FormControl', value: this.reasonDetail(), oninput: m.withAttr('value', this.reasonDetail) }) : ''
                    )
                  )
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    Button,
                    {
                      className: 'Button Button--primary Button--block',
                      type: 'submit',
                      loading: this.loading,
                      disabled: !this.reason() },
                    app.translator.trans('flarum-flags.forum.flag_post.submit_button')
                  )
                )
              )
            );
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            var _this2 = this;

            e.preventDefault();

            this.loading = true;

            app.store.createRecord('flags').save({
              reason: this.reason() === 'other' ? null : this.reason(),
              reasonDetail: this.reasonDetail(),
              relationships: {
                user: app.session.user,
                post: this.props.post
              }
            }).then(function () {
              return _this2.success = true;
            }).catch(function () {}).then(this.loaded.bind(this));
          }
        }]);
        return FlagPostModal;
      }(Modal);

      _export('default', FlagPostModal);
    }
  };
});;
'use strict';

System.register('flarum/flags/components/FlagsDropdown', ['flarum/components/NotificationsDropdown', 'flarum/flags/components/FlagList'], function (_export, _context) {
  "use strict";

  var NotificationsDropdown, FlagList, FlagsDropdown;
  return {
    setters: [function (_flarumComponentsNotificationsDropdown) {
      NotificationsDropdown = _flarumComponentsNotificationsDropdown.default;
    }, function (_flarumFlagsComponentsFlagList) {
      FlagList = _flarumFlagsComponentsFlagList.default;
    }],
    execute: function () {
      FlagsDropdown = function (_NotificationsDropdow) {
        babelHelpers.inherits(FlagsDropdown, _NotificationsDropdow);

        function FlagsDropdown() {
          babelHelpers.classCallCheck(this, FlagsDropdown);
          return babelHelpers.possibleConstructorReturn(this, (FlagsDropdown.__proto__ || Object.getPrototypeOf(FlagsDropdown)).apply(this, arguments));
        }

        babelHelpers.createClass(FlagsDropdown, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(FlagsDropdown.prototype.__proto__ || Object.getPrototypeOf(FlagsDropdown.prototype), 'init', this).call(this);

            this.list = new FlagList();
          }
        }, {
          key: 'goToRoute',
          value: function goToRoute() {
            m.route(app.route('flags'));
          }
        }, {
          key: 'getUnreadCount',
          value: function getUnreadCount() {
            return app.cache.flags ? app.cache.flags.length : app.forum.attribute('flagsCount');
          }
        }, {
          key: 'getNewCount',
          value: function getNewCount() {
            return app.session.user.attribute('newFlagsCount');
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            props.label = props.label || app.translator.trans('flarum-flags.forum.flagged_posts.tooltip');
            props.icon = props.icon || 'flag';

            babelHelpers.get(FlagsDropdown.__proto__ || Object.getPrototypeOf(FlagsDropdown), 'initProps', this).call(this, props);
          }
        }]);
        return FlagsDropdown;
      }(NotificationsDropdown);

      _export('default', FlagsDropdown);
    }
  };
});;
'use strict';

System.register('flarum/flags/components/FlagsPage', ['flarum/components/Page', 'flarum/flags/components/FlagList'], function (_export, _context) {
  "use strict";

  var Page, FlagList, FlagsPage;
  return {
    setters: [function (_flarumComponentsPage) {
      Page = _flarumComponentsPage.default;
    }, function (_flarumFlagsComponentsFlagList) {
      FlagList = _flarumFlagsComponentsFlagList.default;
    }],
    execute: function () {
      FlagsPage = function (_Page) {
        babelHelpers.inherits(FlagsPage, _Page);

        function FlagsPage() {
          babelHelpers.classCallCheck(this, FlagsPage);
          return babelHelpers.possibleConstructorReturn(this, (FlagsPage.__proto__ || Object.getPrototypeOf(FlagsPage)).apply(this, arguments));
        }

        babelHelpers.createClass(FlagsPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(FlagsPage.prototype.__proto__ || Object.getPrototypeOf(FlagsPage.prototype), 'init', this).call(this);

            app.history.push('flags');

            this.list = new FlagList();
            this.list.load();

            this.bodyClass = 'App--flags';
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'FlagsPage' },
              this.list.render()
            );
          }
        }]);
        return FlagsPage;
      }(Page);

      _export('default', FlagsPage);
    }
  };
});;
'use strict';

System.register('flarum/flags/main', ['flarum/app', 'flarum/Model', 'flarum/flags/models/Flag', 'flarum/flags/components/FlagsPage', 'flarum/flags/addFlagControl', 'flarum/flags/addFlagsDropdown', 'flarum/flags/addFlagsToPosts'], function (_export, _context) {
  "use strict";

  var app, Model, Flag, FlagsPage, addFlagControl, addFlagsDropdown, addFlagsToPosts;
  return {
    setters: [function (_flarumApp) {
      app = _flarumApp.default;
    }, function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumFlagsModelsFlag) {
      Flag = _flarumFlagsModelsFlag.default;
    }, function (_flarumFlagsComponentsFlagsPage) {
      FlagsPage = _flarumFlagsComponentsFlagsPage.default;
    }, function (_flarumFlagsAddFlagControl) {
      addFlagControl = _flarumFlagsAddFlagControl.default;
    }, function (_flarumFlagsAddFlagsDropdown) {
      addFlagsDropdown = _flarumFlagsAddFlagsDropdown.default;
    }, function (_flarumFlagsAddFlagsToPosts) {
      addFlagsToPosts = _flarumFlagsAddFlagsToPosts.default;
    }],
    execute: function () {

      app.initializers.add('flarum-flags', function () {
        app.store.models.posts.prototype.flags = Model.hasMany('flags');
        app.store.models.posts.prototype.canFlag = Model.attribute('canFlag');

        app.store.models.flags = Flag;

        app.routes.flags = { path: '/flags', component: m(FlagsPage, null) };

        addFlagControl();
        addFlagsDropdown();
        addFlagsToPosts();
      });
    }
  };
});;
'use strict';

System.register('flarum/flags/models/Flag', ['flarum/Model', 'flarum/utils/mixin'], function (_export, _context) {
  "use strict";

  var Model, mixin, Flag;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumUtilsMixin) {
      mixin = _flarumUtilsMixin.default;
    }],
    execute: function () {
      Flag = function (_Model) {
        babelHelpers.inherits(Flag, _Model);

        function Flag() {
          babelHelpers.classCallCheck(this, Flag);
          return babelHelpers.possibleConstructorReturn(this, (Flag.__proto__ || Object.getPrototypeOf(Flag)).apply(this, arguments));
        }

        return Flag;
      }(Model);

      babelHelpers.extends(Flag.prototype, {
        type: Model.attribute('type'),
        reason: Model.attribute('reason'),
        reasonDetail: Model.attribute('reasonDetail'),
        time: Model.attribute('time', Model.transformDate),

        post: Model.hasOne('post'),
        user: Model.hasOne('user')
      });

      _export('default', Flag);
    }
  };
});