import { extend, override } from 'flarum/extend';
import PermissionGrid from 'flarum/components/PermissionGrid';
import PermissionDropdown from 'flarum/components/PermissionDropdown';
import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';

import tagLabel from 'flarum/tags/helpers/tagLabel';
import tagIcon from 'flarum/tags/helpers/tagIcon';
import sortTags from 'flarum/tags/utils/sortTags';

export default function() {
  override(app, 'getRequiredPermissions', (original, permission) => {
    const tagPrefix = permission.match(/^tag\d+\./);
    
    if (tagPrefix) {
      const globalPermission = permission.substr(tagPrefix[0].length);

      const required = original(globalPermission);

      return required.map(required => tagPrefix[0] + required);
    }
    
    return original(permission);
  });

  extend(PermissionGrid.prototype, 'scopeItems', items => {
    sortTags(app.store.all('tags'))
      .filter(tag => tag.isRestricted())
      .forEach(tag => items.add('tag' + tag.id(), {
        label: tagLabel(tag),
        onremove: () => tag.save({isRestricted: false}),
        render: item => {
          if (item.permission === 'viewDiscussions'
            || item.permission === 'startDiscussion'
            || (item.permission && item.permission.indexOf('discussion.') === 0)) {
            return PermissionDropdown.component({
              permission: 'tag' + tag.id() + '.' + item.permission,
              allowGuest: item.allowGuest
            });
          }

          return '';
        }
      }));
  });

  extend(PermissionGrid.prototype, 'scopeControlItems', items => {
    const tags = sortTags(app.store.all('tags').filter(tag => !tag.isRestricted()));

    if (tags.length) {
      items.add('tag', Dropdown.component({
        className: 'Dropdown--restrictByTag',
        buttonClassName: 'Button Button--text',
        label: app.translator.trans('flarum-tags.admin.permissions.restrict_by_tag_heading'),
        icon: 'plus',
        caretIcon: null,
        children: tags.map(tag => Button.component({
          icon: true,
          children: [tagIcon(tag, {className: 'Button-icon'}), ' ', tag.name()],
          onclick: () => tag.save({isRestricted: true})
        }))
      }));
    }
  });
}
