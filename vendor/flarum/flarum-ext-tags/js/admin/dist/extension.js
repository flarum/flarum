;(function(root, factory) {
  if (typeof define === 'function' && define.amd) {
    define(['jquery'], factory);
  } else if (typeof exports === 'object') {
    module.exports = factory(require('jquery'));
  } else {
    root.sortable = factory(root.jQuery);
  }
}(this, function($) {
/*
 * HTML5 Sortable jQuery Plugin
 * https://github.com/voidberg/html5sortable
 *
 * Original code copyright 2012 Ali Farhadi.
 * This version is mantained by Alexandru Badiu <andu@ctrlz.ro> & Lukas Oppermann <lukas@vea.re>
 *
 *
 * Released under the MIT license.
 */
'use strict';
/*
 * variables global to the plugin
 */
var dragging;
var draggingHeight;
var placeholders = $();
var sortables = [];
/*
 * remove event handlers from items
 * @param [jquery Collection] items
 * @info event.h5s (jquery way of namespacing events, to bind multiple handlers to the event)
 */
var _removeItemEvents = function(items) {
  items.off('dragstart.h5s');
  items.off('dragend.h5s');
  items.off('selectstart.h5s');
  items.off('dragover.h5s');
  items.off('dragenter.h5s');
  items.off('drop.h5s');
};
/*
 * remove event handlers from sortable
 * @param [jquery Collection] sortable
 * @info event.h5s (jquery way of namespacing events, to bind multiple handlers to the event)
 */
var _removeSortableEvents = function(sortable) {
  sortable.off('dragover.h5s');
  sortable.off('dragenter.h5s');
  sortable.off('drop.h5s');
};
/*
 * attache ghost to dataTransfer object
 * @param [event] original event
 * @param [object] ghost-object with item, x and y coordinates
 */
var _attachGhost = function(event, ghost) {
  // this needs to be set for HTML5 drag & drop to work
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text', '');

  // check if setDragImage method is available
  if (event.dataTransfer.setDragImage) {
    event.dataTransfer.setDragImage(ghost.item, ghost.x, ghost.y);
  }
};
/**
 * _addGhostPos clones the dragged item and adds it as a Ghost item
 * @param [object] event - the event fired when dragstart is triggered
 * @param [object] ghost - .item = node, draggedItem = jQuery collection
 */
var _addGhostPos = function(e, ghost) {
  if (!ghost.x) {
    ghost.x = parseInt(e.pageX - ghost.draggedItem.offset().left);
  }
  if (!ghost.y) {
    ghost.y = parseInt(e.pageY - ghost.draggedItem.offset().top);
  }
  return ghost;
};
/**
 * _makeGhost decides which way to make a ghost and passes it to attachGhost
 * @param [jQuery selection] $draggedItem - the item that the user drags
 */
var _makeGhost = function($draggedItem) {
  return {
    item: $draggedItem[0],
    draggedItem: $draggedItem
  };
};
/**
 * _getGhost constructs ghost and attaches it to dataTransfer
 * @param [event] event - the original drag event object
 * @param [jQuery selection] $draggedItem - the item that the user drags
 * @param [object] ghostOpt - the ghost options
 */
// TODO: could $draggedItem be replaced by event.target in all instances
var _getGhost = function(event, $draggedItem) {
  // add ghost item & draggedItem to ghost object
  var ghost = _makeGhost($draggedItem);
  // attach ghost position
  ghost = _addGhostPos(event, ghost);
  // attach ghost to dataTransfer
  _attachGhost(event, ghost);
};
/*
 * return options if not set on sortable already
 * @param [object] soptions
 * @param [object] options
 */
var _getOptions = function(soptions, options) {
  if (typeof soptions === 'undefined') {
    return options;
  }
  return soptions;
};
/*
 * remove data from sortable
 * @param [jquery Collection] a single sortable
 */
var _removeSortableData = function(sortable) {
  sortable.removeData('opts');
  sortable.removeData('connectWith');
  sortable.removeData('items');
  sortable.removeAttr('aria-dropeffect');
};
/*
 * remove data from items
 * @param [jquery Collection] items
 */
var _removeItemData = function(items) {
  items.removeAttr('aria-grabbed');
  items.removeAttr('draggable');
  items.removeAttr('role');
};
/*
 * check if two lists are connected
 * @param [jquery Collection] items
 */
var _listsConnected = function(curList, destList) {
  if (curList[0] === destList[0]) {
    return true;
  }
  if (curList.data('connectWith') !== undefined) {
    return curList.data('connectWith') === destList.data('connectWith');
  }
  return false;
};
/*
 * destroy the sortable
 * @param [jquery Collection] a single sortable
 */
var _destroySortable = function(sortable) {
  var opts = sortable.data('opts') || {};
  var items = sortable.children(opts.items);
  var handles = opts.handle ? items.find(opts.handle) : items;
  // remove event handlers & data from sortable
  _removeSortableEvents(sortable);
  _removeSortableData(sortable);
  // remove event handlers & data from items
  handles.off('mousedown.h5s');
  _removeItemEvents(items);
  _removeItemData(items);
};
/*
 * enable the sortable
 * @param [jquery Collection] a single sortable
 */
var _enableSortable = function(sortable) {
  var opts = sortable.data('opts');
  var items = sortable.children(opts.items);
  var handles = opts.handle ? items.find(opts.handle) : items;
  sortable.attr('aria-dropeffect', 'move');
  handles.attr('draggable', 'true');
  // IE FIX for ghost
  // can be disabled as it has the side effect that other events
  // (e.g. click) will be ignored
  if (typeof document.createElement('span').dragDrop === 'function' && !opts.disableIEFix) {
    handles.on('mousedown.h5s', function() {
      if (items.index(this) !== -1) {
        this.dragDrop();
      } else {
        $(this).parents(opts.items)[0].dragDrop();
      }
    });
  }
};
/*
 * disable the sortable
 * @param [jquery Collection] a single sortable
 */
var _disableSortable = function(sortable) {
  var opts = sortable.data('opts');
  var items = sortable.children(opts.items);
  var handles = opts.handle ? items.find(opts.handle) : items;
  sortable.attr('aria-dropeffect', 'none');
  handles.attr('draggable', false);
  handles.off('mousedown.h5s');
};
/*
 * reload the sortable
 * @param [jquery Collection] a single sortable
 * @description events need to be removed to not be double bound
 */
var _reloadSortable = function(sortable) {
  var opts = sortable.data('opts');
  var items = sortable.children(opts.items);
  var handles = opts.handle ? items.find(opts.handle) : items;
  // remove event handlers from items
  _removeItemEvents(items);
  handles.off('mousedown.h5s');
  // remove event handlers from sortable
  _removeSortableEvents(sortable);
};
/*
 * public sortable object
 * @param [object|string] options|method
 */
var sortable = function(selector, options) {

  var $sortables = $(selector);
  var method = String(options);

  options = $.extend({
    connectWith: false,
    placeholder: null,
    // dragImage can be null or a jQuery element
    dragImage: null,
    disableIEFix: false,
    placeholderClass: 'sortable-placeholder',
    draggingClass: 'sortable-dragging'
  }, options);

  /* TODO: maxstatements should be 25, fix and remove line below */
  /*jshint maxstatements:false */
  return $sortables.each(function() {

    var $sortable = $(this);

    if (/enable|disable|destroy/.test(method)) {
      sortable[method]($sortable);
      return;
    }

    // get options & set options on sortable
    options = _getOptions($sortable.data('opts'), options);
    $sortable.data('opts', options);
    // reset sortable
    _reloadSortable($sortable);
    // initialize
    var items = $sortable.children(options.items);
    var index;
    var startParent;
    var newParent;
    var placeholder = (options.placeholder === null) ? $('<' + (/^ul|ol$/i.test(this.tagName) ? 'li' : 'div') + ' class="' + options.placeholderClass + '"/>') : $(options.placeholder).addClass(options.placeholderClass);

    // setup sortable ids
    if (!$sortable.attr('data-sortable-id')) {
      var id = sortables.length;
      sortables[id] = $sortable;
      $sortable.attr('data-sortable-id', id);
      items.attr('data-item-sortable-id', id);
    }

    $sortable.data('items', options.items);
    placeholders = placeholders.add(placeholder);
    if (options.connectWith) {
      $sortable.data('connectWith', options.connectWith);
    }

    _enableSortable($sortable);
    items.attr('role', 'option');
    items.attr('aria-grabbed', 'false');

    // Handle drag events on draggable items
    items.on('dragstart.h5s', function(e) {
      e.stopImmediatePropagation();

      if (options.dragImage) {
        _attachGhost(e.originalEvent, {
          item: options.dragImage,
          x: 0,
          y: 0
        });
        console.log('WARNING: dragImage option is deprecated' +
        ' and will be removed in the future!');
      } else {
        // add transparent clone or other ghost to cursor
        _getGhost(e.originalEvent, $(this), options.dragImage);
      }
      // cache selsection & add attr for dragging
      dragging = $(this);
      dragging.addClass(options.draggingClass);
      dragging.attr('aria-grabbed', 'true');
      // grab values
      index = dragging.index();
      draggingHeight = dragging.height();
      startParent = $(this).parent();
      // trigger sortstar update
      dragging.parent().triggerHandler('sortstart', {
        item: dragging,
        startparent: startParent
      });
    });
    // Handle drag events on draggable items
    items.on('dragend.h5s', function() {
      if (!dragging) {
        return;
      }
      // remove dragging attributes and show item
      dragging.removeClass(options.draggingClass);
      dragging.attr('aria-grabbed', 'false');
      dragging.show();

      placeholders.detach();
      newParent = $(this).parent();
      dragging.parent().triggerHandler('sortstop', {
        item: dragging,
        startparent: startParent,
      });
      if (index !== dragging.index() ||
          startParent.get(0) !== newParent.get(0)) {
        dragging.parent().triggerHandler('sortupdate', {
          item: dragging,
          index: newParent.children(newParent.data('items')).index(dragging),
          oldindex: items.index(dragging),
          elementIndex: dragging.index(),
          oldElementIndex: index,
          startparent: startParent,
          endparent: newParent
        });
      }
      dragging = null;
      draggingHeight = null;
    });
    // Handle drop event on sortable & placeholder
    // TODO: REMOVE placeholder?????
    $(this).add([placeholder]).on('drop.h5s', function(e) {
      if (!_listsConnected($sortable, $(dragging).parent())) {
        return;
      }

      e.stopPropagation();
      placeholders.filter(':visible').after(dragging);
      dragging.trigger('dragend.h5s');
      return false;
    });

    // Handle dragover and dragenter events on draggable items
    // TODO: REMOVE placeholder?????
    items.add([this, placeholder]).on('dragover.h5s dragenter.h5s', function(e) {
      if (!_listsConnected($sortable, $(dragging).parent())) {
        return;
      }

      e.preventDefault();
      e.originalEvent.dataTransfer.dropEffect = 'move';
      if (items.is(this)) {
        var thisHeight = $(this).height();
        if (options.forcePlaceholderSize) {
          placeholder.height(draggingHeight);
        }

        // Check if $(this) is bigger than the draggable. If it is, we have to define a dead zone to prevent flickering
        if (thisHeight > draggingHeight) {
          // Dead zone?
          var deadZone = thisHeight - draggingHeight;
          var offsetTop = $(this).offset().top;
          if (placeholder.index() < $(this).index() &&
              e.originalEvent.pageY < offsetTop + deadZone) {
            return false;
          }
          if (placeholder.index() > $(this).index() &&
              e.originalEvent.pageY > offsetTop + thisHeight - deadZone) {
            return false;
          }
        }

        dragging.hide();
        if (placeholder.index() < $(this).index()) {
          $(this).after(placeholder);
        } else {
          $(this).before(placeholder);
        }
        placeholders.not(placeholder).detach();
      } else {
        if (!placeholders.is(this) && !$(this).children(options.items).length) {
          placeholders.detach();
          $(this).append(placeholder);
        }
      }
      return false;
    });
  });
};

sortable.destroy = function(sortable) {
  _destroySortable(sortable);
};

sortable.enable = function(sortable) {
  _enableSortable(sortable);
};

sortable.disable = function(sortable) {
  _disableSortable(sortable);
};

$.fn.sortable = function(options) {
  return sortable(this, options);
};

return sortable;
}));
;
'use strict';

System.register('flarum/tags/addTagChangePermission', ['flarum/extend', 'flarum/components/PermissionGrid', 'flarum/components/SettingDropdown'], function (_export, _context) {
  "use strict";

  var extend, PermissionGrid, SettingDropdown;

  _export('default', function () {
    extend(PermissionGrid.prototype, 'startItems', function (items) {
      items.add('allowTagChange', {
        icon: 'tag',
        label: app.translator.trans('flarum-tags.admin.permissions.allow_edit_tags_label'),
        setting: function setting() {
          var minutes = parseInt(app.data.settings.allow_tag_change, 10);

          return SettingDropdown.component({
            defaultLabel: minutes ? app.translator.transChoice('core.admin.permissions_controls.allow_some_minutes_button', minutes, { count: minutes }) : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
            key: 'allow_tag_change',
            options: [{ value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') }, { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') }, { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') }]
          });
        }
      }, 90);
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsPermissionGrid) {
      PermissionGrid = _flarumComponentsPermissionGrid.default;
    }, function (_flarumComponentsSettingDropdown) {
      SettingDropdown = _flarumComponentsSettingDropdown.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/addTagPermission', ['flarum/extend', 'flarum/components/PermissionGrid'], function (_export, _context) {
  "use strict";

  var extend, PermissionGrid;

  _export('default', function () {
    extend(PermissionGrid.prototype, 'moderateItems', function (items) {
      items.add('tag', {
        icon: 'tag',
        label: app.translator.trans('flarum-tags.admin.permissions.tag_discussions_label'),
        permission: 'discussion.tag'
      }, 95);
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsPermissionGrid) {
      PermissionGrid = _flarumComponentsPermissionGrid.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/addTagsHomePageOption', ['flarum/extend', 'flarum/components/BasicsPage'], function (_export, _context) {
  "use strict";

  var extend, BasicsPage;

  _export('default', function () {
    extend(BasicsPage.prototype, 'homePageItems', function (items) {
      items.add('tags', {
        path: '/tags',
        label: app.translator.trans('flarum-tags.admin.basics.tags_label')
      });
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsBasicsPage) {
      BasicsPage = _flarumComponentsBasicsPage.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/addTagsPane', ['flarum/extend', 'flarum/components/AdminNav', 'flarum/components/AdminLinkButton', 'flarum/tags/components/TagsPage'], function (_export, _context) {
  "use strict";

  var extend, AdminNav, AdminLinkButton, TagsPage;

  _export('default', function () {
    app.routes.tags = { path: '/tags', component: TagsPage.component() };

    app.extensionSettings['flarum-tags'] = function () {
      return m.route(app.route('tags'));
    };

    extend(AdminNav.prototype, 'items', function (items) {
      items.add('tags', AdminLinkButton.component({
        href: app.route('tags'),
        icon: 'tags',
        children: app.translator.trans('flarum-tags.admin.nav.tags_button'),
        description: app.translator.trans('flarum-tags.admin.nav.tags_text')
      }));
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsAdminNav) {
      AdminNav = _flarumComponentsAdminNav.default;
    }, function (_flarumComponentsAdminLinkButton) {
      AdminLinkButton = _flarumComponentsAdminLinkButton.default;
    }, function (_flarumTagsComponentsTagsPage) {
      TagsPage = _flarumTagsComponentsTagsPage.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/addTagsPermissionScope', ['flarum/extend', 'flarum/components/PermissionGrid', 'flarum/components/PermissionDropdown', 'flarum/components/Dropdown', 'flarum/components/Button', 'flarum/tags/helpers/tagLabel', 'flarum/tags/helpers/tagIcon', 'flarum/tags/utils/sortTags'], function (_export, _context) {
  "use strict";

  var extend, override, PermissionGrid, PermissionDropdown, Dropdown, Button, tagLabel, tagIcon, sortTags;

  _export('default', function () {
    override(app, 'getRequiredPermissions', function (original, permission) {
      var tagPrefix = permission.match(/^tag\d+\./);

      if (tagPrefix) {
        var globalPermission = permission.substr(tagPrefix[0].length);

        var required = original(globalPermission);

        return required.map(function (required) {
          return tagPrefix[0] + required;
        });
      }

      return original(permission);
    });

    extend(PermissionGrid.prototype, 'scopeItems', function (items) {
      sortTags(app.store.all('tags')).filter(function (tag) {
        return tag.isRestricted();
      }).forEach(function (tag) {
        return items.add('tag' + tag.id(), {
          label: tagLabel(tag),
          onremove: function onremove() {
            return tag.save({ isRestricted: false });
          },
          render: function render(item) {
            if (item.permission === 'viewDiscussions' || item.permission === 'startDiscussion' || item.permission && item.permission.indexOf('discussion.') === 0) {
              return PermissionDropdown.component({
                permission: 'tag' + tag.id() + '.' + item.permission,
                allowGuest: item.allowGuest
              });
            }

            return '';
          }
        });
      });
    });

    extend(PermissionGrid.prototype, 'scopeControlItems', function (items) {
      var tags = sortTags(app.store.all('tags').filter(function (tag) {
        return !tag.isRestricted();
      }));

      if (tags.length) {
        items.add('tag', Dropdown.component({
          className: 'Dropdown--restrictByTag',
          buttonClassName: 'Button Button--text',
          label: app.translator.trans('flarum-tags.admin.permissions.restrict_by_tag_heading'),
          icon: 'plus',
          caretIcon: null,
          children: tags.map(function (tag) {
            return Button.component({
              icon: true,
              children: [tagIcon(tag, { className: 'Button-icon' }), ' ', tag.name()],
              onclick: function onclick() {
                return tag.save({ isRestricted: true });
              }
            });
          })
        }));
      }
    });
  });

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
      override = _flarumExtend.override;
    }, function (_flarumComponentsPermissionGrid) {
      PermissionGrid = _flarumComponentsPermissionGrid.default;
    }, function (_flarumComponentsPermissionDropdown) {
      PermissionDropdown = _flarumComponentsPermissionDropdown.default;
    }, function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumTagsHelpersTagLabel) {
      tagLabel = _flarumTagsHelpersTagLabel.default;
    }, function (_flarumTagsHelpersTagIcon) {
      tagIcon = _flarumTagsHelpersTagIcon.default;
    }, function (_flarumTagsUtilsSortTags) {
      sortTags = _flarumTagsUtilsSortTags.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/components/EditTagModal', ['flarum/components/Modal', 'flarum/components/Button', 'flarum/utils/string', 'flarum/tags/helpers/tagLabel'], function (_export, _context) {
  "use strict";

  var Modal, Button, slug, tagLabel, EditTagModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumUtilsString) {
      slug = _flarumUtilsString.slug;
    }, function (_flarumTagsHelpersTagLabel) {
      tagLabel = _flarumTagsHelpersTagLabel.default;
    }],
    execute: function () {
      EditTagModal = function (_Modal) {
        babelHelpers.inherits(EditTagModal, _Modal);

        function EditTagModal() {
          babelHelpers.classCallCheck(this, EditTagModal);
          return babelHelpers.possibleConstructorReturn(this, (EditTagModal.__proto__ || Object.getPrototypeOf(EditTagModal)).apply(this, arguments));
        }

        babelHelpers.createClass(EditTagModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(EditTagModal.prototype.__proto__ || Object.getPrototypeOf(EditTagModal.prototype), 'init', this).call(this);

            this.tag = this.props.tag || app.store.createRecord('tags');

            this.name = m.prop(this.tag.name() || '');
            this.slug = m.prop(this.tag.slug() || '');
            this.description = m.prop(this.tag.description() || '');
            this.color = m.prop(this.tag.color() || '');
            this.isHidden = m.prop(this.tag.isHidden() || false);
          }
        }, {
          key: 'className',
          value: function className() {
            return 'EditTagModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return this.name() ? tagLabel({
              name: this.name,
              color: this.color
            }) : app.translator.trans('flarum-tags.admin.edit_tag.title');
          }
        }, {
          key: 'content',
          value: function content() {
            var _this2 = this;

            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form' },
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    app.translator.trans('flarum-tags.admin.edit_tag.name_label')
                  ),
                  m('input', { className: 'FormControl', placeholder: app.translator.trans('flarum-tags.admin.edit_tag.name_placeholder'), value: this.name(), oninput: function oninput(e) {
                      _this2.name(e.target.value);
                      _this2.slug(slug(e.target.value));
                    } })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    app.translator.trans('flarum-tags.admin.edit_tag.slug_label')
                  ),
                  m('input', { className: 'FormControl', value: this.slug(), oninput: m.withAttr('value', this.slug) })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    app.translator.trans('flarum-tags.admin.edit_tag.description_label')
                  ),
                  m('textarea', { className: 'FormControl', value: this.description(), oninput: m.withAttr('value', this.description) })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    app.translator.trans('flarum-tags.admin.edit_tag.color_label')
                  ),
                  m('input', { className: 'FormControl', placeholder: '#aaaaaa', value: this.color(), oninput: m.withAttr('value', this.color) })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'div',
                    null,
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'checkbox', value: '1', checked: this.isHidden(), onchange: m.withAttr('checked', this.isHidden) }),
                      app.translator.trans('flarum-tags.admin.edit_tag.hide_label')
                    )
                  )
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Button.component({
                    type: 'submit',
                    className: 'Button Button--primary EditTagModal-save',
                    loading: this.loading,
                    children: app.translator.trans('flarum-tags.admin.edit_tag.submit_button')
                  }),
                  this.tag.exists ? m(
                    'button',
                    { type: 'button', className: 'Button EditTagModal-delete', onclick: this.delete.bind(this) },
                    app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_button')
                  ) : ''
                )
              )
            );
          }
        }, {
          key: 'submitData',
          value: function submitData() {
            return {
              name: this.name(),
              slug: this.slug(),
              description: this.description(),
              color: this.color(),
              isHidden: this.isHidden()
            };
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            var _this3 = this;

            e.preventDefault();

            this.loading = true;

            this.tag.save(this.submitData()).then(function () {
              return _this3.hide();
            }, function (response) {
              _this3.loading = false;
              _this3.handleErrors(response);
            });
          }
        }, {
          key: 'delete',
          value: function _delete() {
            var _this4 = this;

            if (confirm(app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_confirmation'))) {
              var children = app.store.all('tags').filter(function (tag) {
                return tag.parent() === _this4.tag;
              });

              this.tag.delete().then(function () {
                children.forEach(function (tag) {
                  return tag.pushData({
                    attributes: { isChild: false },
                    relationships: { parent: null }
                  });
                });
                m.redraw();
              });

              this.hide();
            }
          }
        }]);
        return EditTagModal;
      }(Modal);

      _export('default', EditTagModal);
    }
  };
});;
'use strict';

System.register('flarum/tags/components/TagSettingsModal', ['flarum/components/SettingsModal'], function (_export, _context) {
  "use strict";

  var SettingsModal, TagSettingsModal;
  return {
    setters: [function (_flarumComponentsSettingsModal) {
      SettingsModal = _flarumComponentsSettingsModal.default;
    }],
    execute: function () {
      TagSettingsModal = function (_SettingsModal) {
        babelHelpers.inherits(TagSettingsModal, _SettingsModal);

        function TagSettingsModal() {
          babelHelpers.classCallCheck(this, TagSettingsModal);
          return babelHelpers.possibleConstructorReturn(this, (TagSettingsModal.__proto__ || Object.getPrototypeOf(TagSettingsModal)).apply(this, arguments));
        }

        babelHelpers.createClass(TagSettingsModal, [{
          key: 'setMinTags',
          value: function setMinTags(minTags, maxTags, value) {
            minTags(value);
            maxTags(Math.max(value, maxTags()));
          }
        }, {
          key: 'className',
          value: function className() {
            return 'TagSettingsModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-tags.admin.tag_settings.title');
          }
        }, {
          key: 'form',
          value: function form() {
            var minPrimaryTags = this.setting('flarum-tags.min_primary_tags', 0);
            var maxPrimaryTags = this.setting('flarum-tags.max_primary_tags', 0);

            var minSecondaryTags = this.setting('flarum-tags.min_secondary_tags', 0);
            var maxSecondaryTags = this.setting('flarum-tags.max_secondary_tags', 0);

            return [m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-tags.admin.tag_settings.required_primary_heading')
              ),
              m(
                'div',
                { className: 'helpText' },
                app.translator.trans('flarum-tags.admin.tag_settings.required_primary_text')
              ),
              m(
                'div',
                { className: 'TagSettingsModal-rangeInput' },
                m('input', { className: 'FormControl',
                  type: 'number',
                  min: '0',
                  value: minPrimaryTags(),
                  oninput: m.withAttr('value', this.setMinTags.bind(this, minPrimaryTags, maxPrimaryTags)) }),
                app.translator.trans('flarum-tags.admin.tag_settings.range_separator_text'),
                m('input', { className: 'FormControl',
                  type: 'number',
                  min: minPrimaryTags(),
                  bidi: maxPrimaryTags })
              )
            ), m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_heading')
              ),
              m(
                'div',
                { className: 'helpText' },
                app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_text')
              ),
              m(
                'div',
                { className: 'TagSettingsModal-rangeInput' },
                m('input', { className: 'FormControl',
                  type: 'number',
                  min: '0',
                  value: minSecondaryTags(),
                  oninput: m.withAttr('value', this.setMinTags.bind(this, minSecondaryTags, maxSecondaryTags)) }),
                app.translator.trans('flarum-tags.admin.tag_settings.range_separator_text'),
                m('input', { className: 'FormControl',
                  type: 'number',
                  min: minSecondaryTags(),
                  bidi: maxSecondaryTags })
              )
            )];
          }
        }]);
        return TagSettingsModal;
      }(SettingsModal);

      _export('default', TagSettingsModal);
    }
  };
});;
'use strict';

System.register('flarum/tags/components/TagsPage', ['flarum/components/Page', 'flarum/components/Button', 'flarum/tags/components/EditTagModal', 'flarum/tags/components/TagSettingsModal', 'flarum/tags/helpers/tagIcon', 'flarum/tags/utils/sortTags'], function (_export, _context) {
  "use strict";

  var Page, Button, EditTagModal, TagSettingsModal, tagIcon, sortTags, TagsPage;


  function tagItem(tag) {
    return m(
      'li',
      { 'data-id': tag.id(), style: { color: tag.color() } },
      m(
        'div',
        { className: 'TagListItem-info' },
        tagIcon(tag),
        m(
          'span',
          { className: 'TagListItem-name' },
          tag.name()
        ),
        Button.component({
          className: 'Button Button--link',
          icon: 'pencil',
          onclick: function onclick() {
            return app.modal.show(new EditTagModal({ tag: tag }));
          }
        })
      ),
      !tag.isChild() && tag.position() !== null ? m(
        'ol',
        { className: 'TagListItem-children' },
        sortTags(app.store.all('tags')).filter(function (child) {
          return child.parent() === tag;
        }).map(tagItem)
      ) : ''
    );
  }

  return {
    setters: [function (_flarumComponentsPage) {
      Page = _flarumComponentsPage.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumTagsComponentsEditTagModal) {
      EditTagModal = _flarumTagsComponentsEditTagModal.default;
    }, function (_flarumTagsComponentsTagSettingsModal) {
      TagSettingsModal = _flarumTagsComponentsTagSettingsModal.default;
    }, function (_flarumTagsHelpersTagIcon) {
      tagIcon = _flarumTagsHelpersTagIcon.default;
    }, function (_flarumTagsUtilsSortTags) {
      sortTags = _flarumTagsUtilsSortTags.default;
    }],
    execute: function () {
      TagsPage = function (_Page) {
        babelHelpers.inherits(TagsPage, _Page);

        function TagsPage() {
          babelHelpers.classCallCheck(this, TagsPage);
          return babelHelpers.possibleConstructorReturn(this, (TagsPage.__proto__ || Object.getPrototypeOf(TagsPage)).apply(this, arguments));
        }

        babelHelpers.createClass(TagsPage, [{
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'TagsPage' },
              m(
                'div',
                { className: 'TagsPage-header' },
                m(
                  'div',
                  { className: 'container' },
                  m(
                    'p',
                    null,
                    app.translator.trans('flarum-tags.admin.tags.about_tags_text')
                  ),
                  Button.component({
                    className: 'Button Button--primary',
                    icon: 'plus',
                    children: app.translator.trans('flarum-tags.admin.tags.create_tag_button'),
                    onclick: function onclick() {
                      return app.modal.show(new EditTagModal());
                    }
                  }),
                  Button.component({
                    className: 'Button',
                    children: app.translator.trans('flarum-tags.admin.tags.settings_button'),
                    onclick: function onclick() {
                      return app.modal.show(new TagSettingsModal());
                    }
                  })
                )
              ),
              m(
                'div',
                { className: 'TagsPage-list' },
                m(
                  'div',
                  { className: 'container' },
                  m(
                    'div',
                    { className: 'TagGroup' },
                    m(
                      'label',
                      null,
                      app.translator.trans('flarum-tags.admin.tags.primary_heading')
                    ),
                    m(
                      'ol',
                      { className: 'TagList TagList--primary' },
                      sortTags(app.store.all('tags')).filter(function (tag) {
                        return tag.position() !== null && !tag.isChild();
                      }).map(tagItem)
                    )
                  ),
                  m(
                    'div',
                    { className: 'TagGroup' },
                    m(
                      'label',
                      null,
                      app.translator.trans('flarum-tags.admin.tags.secondary_heading')
                    ),
                    m(
                      'ul',
                      { className: 'TagList' },
                      app.store.all('tags').filter(function (tag) {
                        return tag.position() === null;
                      }).sort(function (a, b) {
                        return a.name().localeCompare(b.name());
                      }).map(tagItem)
                    )
                  )
                )
              )
            );
          }
        }, {
          key: 'config',
          value: function config() {
            var _this2 = this;

            this.$('ol, ul').sortable({ connectWith: 'primary' }).on('sortupdate', function (e, ui) {
              // If we've moved a tag from 'primary' to 'secondary', then we'll update
              // its attributes in our local store so that when we redraw the change
              // will be made.
              if (ui.startparent.is('ol') && ui.endparent.is('ul')) {
                app.store.getById('tags', ui.item.data('id')).pushData({
                  attributes: {
                    position: null,
                    isChild: false
                  },
                  relationships: { parent: null }
                });
              }

              // Construct an array of primary tag IDs and their children, in the same
              // order that they have been arranged in.
              var order = _this2.$('.TagList--primary > li').map(function () {
                return {
                  id: $(this).data('id'),
                  children: $(this).find('li').map(function () {
                    return $(this).data('id');
                  }).get()
                };
              }).get();

              // Now that we have an accurate representation of the order which the
              // primary tags are in, we will update the tag attributes in our local
              // store to reflect this order.
              order.forEach(function (tag, i) {
                var parent = app.store.getById('tags', tag.id);
                parent.pushData({
                  attributes: {
                    position: i,
                    isChild: false
                  },
                  relationships: { parent: null }
                });

                tag.children.forEach(function (child, j) {
                  app.store.getById('tags', child).pushData({
                    attributes: {
                      position: j,
                      isChild: true
                    },
                    relationships: { parent: parent }
                  });
                });
              });

              app.request({
                url: app.forum.attribute('apiUrl') + '/tags/order',
                method: 'POST',
                data: { order: order }
              });

              // A diff redraw won't work here, because sortable has mucked around
              // with the DOM which will confuse Mithril's diffing algorithm. Instead
              // we force a full reconstruction of the DOM.
              m.redraw.strategy('all');
              m.redraw();
            });
          }
        }]);
        return TagsPage;
      }(Page);

      _export('default', TagsPage);
    }
  };
});;
'use strict';

System.register('flarum/tags/helpers/tagIcon', [], function (_export, _context) {
  "use strict";

  function tagIcon(tag) {
    var attrs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    attrs.className = 'icon TagIcon ' + (attrs.className || '');

    if (tag) {
      attrs.style = attrs.style || {};
      attrs.style.backgroundColor = tag.color();
    } else {
      attrs.className += ' untagged';
    }

    return m('span', attrs);
  }

  _export('default', tagIcon);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/helpers/tagLabel', ['flarum/utils/extract'], function (_export, _context) {
  "use strict";

  var extract;
  function tagLabel(tag) {
    var attrs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    attrs.style = attrs.style || {};
    attrs.className = 'TagLabel ' + (attrs.className || '');

    var link = extract(attrs, 'link');

    if (tag) {
      var color = tag.color();
      if (color) {
        attrs.style.backgroundColor = attrs.style.color = color;
        attrs.className += ' colored';
      }

      if (link) {
        attrs.title = tag.description() || '';
        attrs.href = app.route('tag', { tags: tag.slug() });
        attrs.config = m.route;
      }
    } else {
      attrs.className += ' untagged';
    }

    return m(link ? 'a' : 'span', attrs, m(
      'span',
      { className: 'TagLabel-text' },
      tag ? tag.name() : app.translator.trans('flarum-tags.lib.deleted_tag_text')
    ));
  }

  _export('default', tagLabel);

  return {
    setters: [function (_flarumUtilsExtract) {
      extract = _flarumUtilsExtract.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/helpers/tagsLabel', ['flarum/utils/extract', 'flarum/tags/helpers/tagLabel', 'flarum/tags/utils/sortTags'], function (_export, _context) {
  "use strict";

  var extract, tagLabel, sortTags;
  function tagsLabel(tags) {
    var attrs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    var children = [];
    var link = extract(attrs, 'link');

    attrs.className = 'TagsLabel ' + (attrs.className || '');

    if (tags) {
      sortTags(tags).forEach(function (tag) {
        if (tag || tags.length === 1) {
          children.push(tagLabel(tag, { link: link }));
        }
      });
    } else {
      children.push(tagLabel());
    }

    return m(
      'span',
      attrs,
      children
    );
  }

  _export('default', tagsLabel);

  return {
    setters: [function (_flarumUtilsExtract) {
      extract = _flarumUtilsExtract.default;
    }, function (_flarumTagsHelpersTagLabel) {
      tagLabel = _flarumTagsHelpersTagLabel.default;
    }, function (_flarumTagsUtilsSortTags) {
      sortTags = _flarumTagsUtilsSortTags.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/tags/main', ['flarum/tags/models/Tag', 'flarum/tags/addTagsPermissionScope', 'flarum/tags/addTagPermission', 'flarum/tags/addTagsPane', 'flarum/tags/addTagsHomePageOption', 'flarum/tags/addTagChangePermission'], function (_export, _context) {
  "use strict";

  var Tag, addTagsPermissionScope, addTagPermission, addTagsPane, addTagsHomePageOption, addTagChangePermission;
  return {
    setters: [function (_flarumTagsModelsTag) {
      Tag = _flarumTagsModelsTag.default;
    }, function (_flarumTagsAddTagsPermissionScope) {
      addTagsPermissionScope = _flarumTagsAddTagsPermissionScope.default;
    }, function (_flarumTagsAddTagPermission) {
      addTagPermission = _flarumTagsAddTagPermission.default;
    }, function (_flarumTagsAddTagsPane) {
      addTagsPane = _flarumTagsAddTagsPane.default;
    }, function (_flarumTagsAddTagsHomePageOption) {
      addTagsHomePageOption = _flarumTagsAddTagsHomePageOption.default;
    }, function (_flarumTagsAddTagChangePermission) {
      addTagChangePermission = _flarumTagsAddTagChangePermission.default;
    }],
    execute: function () {

      app.initializers.add('flarum-tags', function (app) {
        app.store.models.tags = Tag;

        addTagsPermissionScope();
        addTagPermission();
        addTagsPane();
        addTagsHomePageOption();
        addTagChangePermission();
      });
    }
  };
});;
'use strict';

System.register('flarum/tags/models/Tag', ['flarum/Model', 'flarum/utils/mixin', 'flarum/utils/computed'], function (_export, _context) {
  "use strict";

  var Model, mixin, computed, Tag;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumUtilsMixin) {
      mixin = _flarumUtilsMixin.default;
    }, function (_flarumUtilsComputed) {
      computed = _flarumUtilsComputed.default;
    }],
    execute: function () {
      Tag = function (_mixin) {
        babelHelpers.inherits(Tag, _mixin);

        function Tag() {
          babelHelpers.classCallCheck(this, Tag);
          return babelHelpers.possibleConstructorReturn(this, (Tag.__proto__ || Object.getPrototypeOf(Tag)).apply(this, arguments));
        }

        return Tag;
      }(mixin(Model, {
        name: Model.attribute('name'),
        slug: Model.attribute('slug'),
        description: Model.attribute('description'),

        color: Model.attribute('color'),
        backgroundUrl: Model.attribute('backgroundUrl'),
        backgroundMode: Model.attribute('backgroundMode'),

        position: Model.attribute('position'),
        parent: Model.hasOne('parent'),
        defaultSort: Model.attribute('defaultSort'),
        isChild: Model.attribute('isChild'),
        isHidden: Model.attribute('isHidden'),

        discussionsCount: Model.attribute('discussionsCount'),
        lastTime: Model.attribute('lastTime', Model.transformDate),
        lastDiscussion: Model.hasOne('lastDiscussion'),

        isRestricted: Model.attribute('isRestricted'),
        canStartDiscussion: Model.attribute('canStartDiscussion'),
        canAddToDiscussion: Model.attribute('canAddToDiscussion'),

        isPrimary: computed('position', 'parent', function (position, parent) {
          return position !== null && parent === false;
        })
      }));

      _export('default', Tag);
    }
  };
});;
"use strict";

System.register("flarum/tags/utils/sortTags", [], function (_export, _context) {
  "use strict";

  function sortTags(tags) {
    return tags.slice(0).sort(function (a, b) {
      var aPos = a.position();
      var bPos = b.position();

      // If they're both secondary tags, sort them by their discussions count,
      // descending.
      if (aPos === null && bPos === null) return b.discussionsCount() - a.discussionsCount();

      // If just one is a secondary tag, then the primary tag should
      // come first.
      if (bPos === null) return -1;
      if (aPos === null) return 1;

      // If we've made it this far, we know they're both primary tags. So we'll
      // need to see if they have parents.
      var aParent = a.parent();
      var bParent = b.parent();

      // If they both have the same parent, then their positions are local,
      // so we can compare them directly.
      if (aParent === bParent) return aPos - bPos;

      // If they are both child tags, then we will compare the positions of their
      // parents.
      else if (aParent && bParent) return aParent.position() - bParent.position();

        // If we are comparing a child tag with its parent, then we let the parent
        // come first. If we are comparing an unrelated parent/child, then we
        // compare both of the parents.
        else if (aParent) return aParent === b ? 1 : aParent.position() - bPos;else if (bParent) return bParent === a ? -1 : aPos - bParent.position();

      return 0;
    });
  }

  _export("default", sortTags);

  return {
    setters: [],
    execute: function () {}
  };
});