'use strict';

System.register('romanzpolski/shawTheme/listInline', ['flarum/extend', 'flarum/Component', 'flarum/helpers/listItems'], function (_export, _context) {
    "use strict";

    var extend, Component, listItems, listInline;
    return {
        setters: [function (_flarumExtend) {
            extend = _flarumExtend.extend;
        }, function (_flarumComponent) {
            Component = _flarumComponent.default;
        }, function (_flarumHelpersListItems) {
            listItems = _flarumHelpersListItems.default;
        }],
        execute: function () {
            listInline = function (_Component) {
                babelHelpers.inherits(listInline, _Component);

                function listInline() {
                    babelHelpers.classCallCheck(this, listInline);
                    return babelHelpers.possibleConstructorReturn(this, (listInline.__proto__ || Object.getPrototypeOf(listInline)).apply(this, arguments));
                }

                babelHelpers.createClass(listInline, [{
                    key: 'init',
                    value: function init() {
                        this.showing = false;
                    }
                }, {
                    key: 'view',
                    value: function view() {
                        var items = this.props.children ? listItems(this.props.children) : [];

                        return m(
                            'div',
                            { className: 'viewNavInline ' + this.props.className + ' itemCount' + items.length + (this.showing ? ' open' : '') },
                            this.getMenu(items)
                        );
                    }
                }, {
                    key: 'getMenu',
                    value: function getMenu(items) {
                        return m(
                            'ul',
                            { className: 'listInline ' + this.props.menuClassName },
                            items
                        );
                    }
                }], [{
                    key: 'initProps',
                    value: function initProps(props) {
                        babelHelpers.get(listInline.__proto__ || Object.getPrototypeOf(listInline), 'initProps', this).call(this, props);
                        props.className = props.className || '';
                        props.buttonClassName = props.buttonClassName || '';
                        props.menuClassName = props.menuClassName || '';
                        props.label = props.label || '';
                        props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'caret-down';
                    }
                }]);
                return listInline;
            }(Component);

            _export('default', listInline);
        }
    };
});;
'use strict';

System.register('romanzpolski/shawTheme/main', ['flarum/extend', 'flarum/components/Post', 'flarum/Component', 'flarum/components/Page', 'flarum/components/HeaderSecondary', 'flarum/components/SessionDropdown', 'flarum/components/Dropdown', 'flarum/components/IndexPage', 'flarum/helpers/listItems', 'flarum/utils/ItemList', 'flarum/components/Button', 'flarum/components/LinkButton', 'flarum/components/SelectDropdown', 'flarum/tags/helpers/tagLabel', 'flarum/tags/utils/sortTags', 'flarum/tags/components/TagsPage', 'flarum/helpers/humanTime', 'flarum/helpers/icon', 'flarum/helpers/avatar', 'flarum/helpers/username', 'romanzpolski/shawTheme/listInline'], function (_export, _context) {
    "use strict";

    var extend, Post, Component, Page, HeaderSecondary, SessionDropdown, Dropdown, IndexPage, listItems, ItemList, Button, LinkButton, SelectDropdown, tagLabel, sortTags, TagsPage, humanTime, icon, avatar, username, listInline;
    return {
        setters: [function (_flarumExtend) {
            extend = _flarumExtend.extend;
        }, function (_flarumComponentsPost) {
            Post = _flarumComponentsPost.default;
        }, function (_flarumComponent) {
            Component = _flarumComponent.default;
        }, function (_flarumComponentsPage) {
            Page = _flarumComponentsPage.default;
        }, function (_flarumComponentsHeaderSecondary) {
            HeaderSecondary = _flarumComponentsHeaderSecondary.default;
        }, function (_flarumComponentsSessionDropdown) {
            SessionDropdown = _flarumComponentsSessionDropdown.default;
        }, function (_flarumComponentsDropdown) {
            Dropdown = _flarumComponentsDropdown.default;
        }, function (_flarumComponentsIndexPage) {
            IndexPage = _flarumComponentsIndexPage.default;
        }, function (_flarumHelpersListItems) {
            listItems = _flarumHelpersListItems.default;
        }, function (_flarumUtilsItemList) {
            ItemList = _flarumUtilsItemList.default;
        }, function (_flarumComponentsButton) {
            Button = _flarumComponentsButton.default;
        }, function (_flarumComponentsLinkButton) {
            LinkButton = _flarumComponentsLinkButton.default;
        }, function (_flarumComponentsSelectDropdown) {
            SelectDropdown = _flarumComponentsSelectDropdown.default;
        }, function (_flarumTagsHelpersTagLabel) {
            tagLabel = _flarumTagsHelpersTagLabel.default;
        }, function (_flarumTagsUtilsSortTags) {
            sortTags = _flarumTagsUtilsSortTags.default;
        }, function (_flarumTagsComponentsTagsPage) {
            TagsPage = _flarumTagsComponentsTagsPage.default;
        }, function (_flarumHelpersHumanTime) {
            humanTime = _flarumHelpersHumanTime.default;
        }, function (_flarumHelpersIcon) {
            icon = _flarumHelpersIcon.default;
        }, function (_flarumHelpersAvatar) {
            avatar = _flarumHelpersAvatar.default;
        }, function (_flarumHelpersUsername) {
            username = _flarumHelpersUsername.default;
        }, function (_romanzpolskiShawThemeListInline) {
            listInline = _romanzpolskiShawThemeListInline.default;
        }],
        execute: function () {

            app.initializers.add('romanzpolski/shawTheme', function () {

                SessionDropdown.prototype.getButtonContent = function () {
                    var user = app.session.user;
                    var attrs = {};
                    attrs.style = { background: '#000' };
                    return [m(
                        'span',
                        { className: 'Button-label' },
                        username(user)
                    ), avatar(user), ' '];
                };

                IndexPage.prototype.viewItems = function () {
                    var _this = this;

                    var items = new ItemList();
                    var sortMap = app.cache.discussionList.sortMap();

                    var sortOptions = {};
                    for (var i in sortMap) {
                        sortOptions[i] = app.translator.trans('core.forum.index_sort.' + i + '_button');
                    }

                    items.add('sort', listInline.component({
                        buttonClassName: 'Button',
                        label: sortOptions[this.params().sort] || Object.keys(sortMap).map(function (key) {
                            return sortOptions[key];
                        })[0],
                        children: Object.keys(sortOptions).map(function (value) {
                            var label = sortOptions[value];
                            var active = (_this.params().sort || Object.keys(sortMap)[0]) === value;

                            return Button.component({
                                className: 'Button',
                                children: label,
                                icon: active ? 'check' : true,
                                onclick: _this.changeSort.bind(_this, value),
                                active: active
                            });
                        })
                    }));

                    return items;
                };

                IndexPage.prototype.sidebarItems = function () {
                    var items = new ItemList();
                    var canStartDiscussion = app.forum.attribute('canStartDiscussion') || !app.session.user;

                    items.add('newDiscussion', Button.component({
                        children: app.translator.trans(canStartDiscussion ? 'core.forum.index.start_discussion_button' : 'core.forum.index.cannot_start_discussion_button'),
                        icon: 'edit',
                        className: 'Button Button--primary IndexPage-newDiscussion',
                        itemClassName: 'App-primaryControl',
                        onclick: this.newDiscussion.bind(this),
                        disabled: !canStartDiscussion
                    }));

                    items.add('nav', SelectDropdown.component({
                        children: this.navItems(this).toArray(),
                        buttonClassName: 'Button',
                        className: 'App-titleControl'
                    }));
                    return items;
                };

                TagsPage.prototype.view = function () {
                    var pinned = this.tags.filter(function (tag) {
                        return tag.position() !== null;
                    });
                    var cloud = this.tags.filter(function (tag) {
                        return tag.position() === null;
                    });

                    return m(
                        'div',
                        { className: 'TagsPage' },
                        IndexPage.prototype.hero(),
                        m(
                            'div',
                            { className: 'container' },
                            m(
                                'nav',
                                { className: 'TagsPage-nav IndexPage-nav sideNav', config: IndexPage.prototype.affixSidebar },
                                m(
                                    'ul',
                                    null,
                                    listItems(IndexPage.prototype.sidebarItems().toArray())
                                )
                            ),
                            m(
                                'div',
                                { className: 'TagsPage-content sideNavOffset' },
                                m(
                                    'ul',
                                    { className: 'TagTiles' },
                                    pinned.map(function (tag) {
                                        var lastDiscussion = tag.lastDiscussion();
                                        var children = sortTags(app.store.all('tags').filter(function (child) {
                                            return child.parent() === tag;
                                        }));
                                        return m(
                                            'li',
                                            { className: 'TagTile bgImg ' + tag.data.attributes.slug + (tag.color() ? ' colored' : ''),
                                                style: { backgroundColor: tag.color() } },
                                            m(
                                                'a',
                                                { className: 'TagTile-info', href: app.route.tag(tag), config: m.route },
                                                m(
                                                    'h3',
                                                    { className: 'TagTile-name' },
                                                    tag.name()
                                                ),
                                                m(
                                                    'p',
                                                    { className: 'TagTile-description' },
                                                    tag.description()
                                                ),
                                                children ? m(
                                                    'div',
                                                    { className: 'TagTile-children' },
                                                    children.map(function (child) {
                                                        return [m(
                                                            'a',
                                                            { href: app.route.tag(child), config: function config(element, isInitialized) {
                                                                    if (isInitialized) return;
                                                                    $(element).on('click', function (e) {
                                                                        return e.stopPropagation();
                                                                    });
                                                                    m.route.apply(this, arguments);
                                                                } },
                                                            child.name()
                                                        ), ' '];
                                                    })
                                                ) : ''
                                            ),
                                            lastDiscussion ? m(
                                                'a',
                                                { className: 'TagTile-lastDiscussion',
                                                    href: app.route.discussion(lastDiscussion, lastDiscussion.lastPostNumber()),
                                                    config: m.route },
                                                m(
                                                    'span',
                                                    { className: 'TagTile-lastDiscussion-title' },
                                                    lastDiscussion.title()
                                                ),
                                                humanTime(lastDiscussion.lastTime())
                                            ) : m('span', { className: 'TagTile-lastDiscussion' })
                                        );
                                    })
                                ),
                                cloud.length ? m(
                                    'div',
                                    { className: 'TagCloud' },
                                    cloud.map(function (tag) {
                                        var color = tag.color();

                                        return [tagLabel(tag, { link: true }), ' '];
                                    })
                                ) : ''
                            )
                        )
                    );
                };

                IndexPage.prototype.view = function () {
                    console.log(this.sidebarItems().toArray());
                    //        console.log(this.viewItems().toArray());
                    return m(
                        'div',
                        { className: 'IndexPage' },
                        this.hero(),
                        m(
                            'div',
                            { className: 'container' },
                            m(
                                'nav',
                                { className: 'IndexPage-nav sideNav' },
                                m(
                                    'ul',
                                    null,
                                    listItems(this.sidebarItems().toArray())
                                )
                            ),
                            m(
                                'div',
                                { className: 'IndexPage-results sideNavOffset' },
                                m(
                                    'div',
                                    { className: 'IndexPage-toolbar' },
                                    m(
                                        'ul',
                                        { className: 'IndexPage-toolbar-view kutas' },
                                        listItems(this.viewItems().toArray())
                                    ),
                                    m(
                                        'ul',
                                        { className: 'IndexPage-toolbar-action' },
                                        listItems(this.actionItems().toArray())
                                    )
                                ),
                                app.cache.discussionList.render()
                            )
                        )
                    );
                };

                /*    extend(Post.prototype, 'view', function(vdom) {
                //        vdom.children.push('<div class="kutas"><p>this is some stuff to add after each post</p></div>');
                        vdom.attrs.style = 'background-color: #fafafa; border-bottom: 1px solid #000';
                    });*/
            });
        }
    };
});;
'use strict';

System.register('romanzpolski/shawTheme/newComponent', ['flarum/extend', 'flarum/Component', 'flarum/helpers/listItems'], function (_export, _context) {
    "use strict";

    var extend, Component, listItems, NewComponent;
    return {
        setters: [function (_flarumExtend) {
            extend = _flarumExtend.extend;
        }, function (_flarumComponent) {
            Component = _flarumComponent.default;
        }, function (_flarumHelpersListItems) {
            listItems = _flarumHelpersListItems.default;
        }],
        execute: function () {
            NewComponent = function (_Component) {
                babelHelpers.inherits(NewComponent, _Component);

                function NewComponent() {
                    babelHelpers.classCallCheck(this, NewComponent);
                    return babelHelpers.possibleConstructorReturn(this, (NewComponent.__proto__ || Object.getPrototypeOf(NewComponent)).apply(this, arguments));
                }

                babelHelpers.createClass(NewComponent, [{
                    key: 'init',
                    value: function init() {
                        this.showing = false;
                    }
                }, {
                    key: 'view',
                    value: function view() {
                        var items = this.props.children ? listItems(this.props.children) : [];

                        return m(
                            'div',
                            { className: 'viewNavInline ' + this.props.className + ' itemCount' + items.length + (this.showing ? ' open' : '') },
                            this.getMenu(items)
                        );
                    }
                }, {
                    key: 'getMenu',
                    value: function getMenu(items) {
                        return m(
                            'ul',
                            { className: 'listInline ' + this.props.menuClassName },
                            items
                        );
                    }
                }], [{
                    key: 'initProps',
                    value: function initProps(props) {
                        babelHelpers.get(NewComponent.__proto__ || Object.getPrototypeOf(NewComponent), 'initProps', this).call(this, props);
                        props.className = props.className || '';
                        props.buttonClassName = props.buttonClassName || '';
                        props.menuClassName = props.menuClassName || '';
                        props.label = props.label || '';
                        props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'caret-down';
                    }
                }]);
                return NewComponent;
            }(Component);

            _export('default', NewComponent);
        }
    };
});