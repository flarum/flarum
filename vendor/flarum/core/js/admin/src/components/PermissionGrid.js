import Component from 'flarum/Component';
import PermissionDropdown from 'flarum/components/PermissionDropdown';
import SettingDropdown from 'flarum/components/SettingDropdown';
import Button from 'flarum/components/Button';
import ItemList from 'flarum/utils/ItemList';
import icon from 'flarum/helpers/icon';

export default class PermissionGrid extends Component {
  init() {
    this.permissions = this.permissionItems().toArray();
  }

  view() {
    const scopes = this.scopeItems().toArray();

    const permissionCells = permission => {
      return scopes.map(scope => (
        <td>
          {scope.render(permission)}
        </td>
      ));
    };

    return (
      <table className="PermissionGrid">
        <thead>
          <tr>
            <td></td>
            {scopes.map(scope => (
              <th>
                {scope.label}{' '}
                {scope.onremove ? Button.component({icon: 'times', className: 'Button Button--text PermissionGrid-removeScope', onclick: scope.onremove}) : ''}
              </th>
            ))}
            <th>{this.scopeControlItems().toArray()}</th>
          </tr>
        </thead>
        {this.permissions.map(section => (
          <tbody>
            <tr className="PermissionGrid-section">
              <th>{section.label}</th>
              {permissionCells(section)}
              <td/>
            </tr>
            {section.children.map(child => (
              <tr className="PermissionGrid-child">
                <th>{icon(child.icon)}{child.label}</th>
                {permissionCells(child)}
                <td/>
              </tr>
            ))}
          </tbody>
        ))}
      </table>
    );
  }

  permissionItems() {
    const items = new ItemList();

    items.add('view', {
      label: app.translator.trans('core.admin.permissions.read_heading'),
      children: this.viewItems().toArray()
    }, 100);

    items.add('start', {
      label: app.translator.trans('core.admin.permissions.create_heading'),
      children: this.startItems().toArray()
    }, 90);

    items.add('reply', {
      label: app.translator.trans('core.admin.permissions.participate_heading'),
      children: this.replyItems().toArray()
    }, 80);

    items.add('moderate', {
      label: app.translator.trans('core.admin.permissions.moderate_heading'),
      children: this.moderateItems().toArray()
    }, 70);

    return items;
  }

  viewItems() {
    const items = new ItemList();

    items.add('viewDiscussions', {
      icon: 'eye',
      label: app.translator.trans('core.admin.permissions.view_discussions_label'),
      permission: 'viewDiscussions',
      allowGuest: true
    }, 100);

    items.add('viewUserList', {
      icon: 'users',
      label: app.translator.trans('core.admin.permissions.view_user_list_label'),
      permission: 'viewUserList',
      allowGuest: true
    }, 100);

    items.add('signUp', {
      icon: 'user-plus',
      label: app.translator.trans('core.admin.permissions.sign_up_label'),
      setting: () => SettingDropdown.component({
        key: 'allow_sign_up',
        options: [
          {value: '1', label: app.translator.trans('core.admin.permissions_controls.signup_open_button')},
          {value: '0', label: app.translator.trans('core.admin.permissions_controls.signup_closed_button')}
        ]
      })
    }, 90);

    return items;
  }

  startItems() {
    const items = new ItemList();

    items.add('start', {
      icon: 'edit',
      label: app.translator.trans('core.admin.permissions.start_discussions_label'),
      permission: 'startDiscussion'
    }, 100);

    items.add('allowRenaming', {
      icon: 'i-cursor',
      label: app.translator.trans('core.admin.permissions.allow_renaming_label'),
      setting: () => {
        const minutes = parseInt(app.data.settings.allow_renaming, 10);

        return SettingDropdown.component({
          defaultLabel: minutes
            ? app.translator.transChoice('core.admin.permissions_controls.allow_some_minutes_button', minutes, {count: minutes})
            : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
          key: 'allow_renaming',
          options: [
            {value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button')},
            {value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button')},
            {value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button')}
          ]
        });
      }
    }, 90);

    return items;
  }

  replyItems() {
    const items = new ItemList();

    items.add('reply', {
      icon: 'reply',
      label: app.translator.trans('core.admin.permissions.reply_to_discussions_label'),
      permission: 'discussion.reply'
    }, 100);

    items.add('allowPostEditing', {
      icon: 'pencil',
      label: app.translator.trans('core.admin.permissions.allow_post_editing_label'),
      setting: () => {
        const minutes = parseInt(app.data.settings.allow_post_editing, 10);

        return SettingDropdown.component({
          defaultLabel: minutes
            ? app.translator.transChoice('core.admin.permissions_controls.allow_some_minutes_button', minutes, {count: minutes})
            : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
          key: 'allow_post_editing',
          options: [
            {value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button')},
            {value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button')},
            {value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button')}
          ]
        });
      }
    }, 90);

    return items;
  }

  moderateItems() {
    const items = new ItemList();

    items.add('viewIpsPosts', {
      icon: 'bullseye',
      label: app.translator.trans('core.admin.permissions.view_post_ips_label'),
      permission: 'discussion.viewIpsPosts'
    }, 110);

    items.add('renameDiscussions', {
      icon: 'i-cursor',
      label: app.translator.trans('core.admin.permissions.rename_discussions_label'),
      permission: 'discussion.rename'
    }, 100);

    items.add('hideDiscussions', {
      icon: 'trash-o',
      label: app.translator.trans('core.admin.permissions.delete_discussions_label'),
      permission: 'discussion.hide'
    }, 90);

    items.add('deleteDiscussions', {
      icon: 'times',
      label: app.translator.trans('core.admin.permissions.delete_discussions_forever_label'),
      permission: 'discussion.delete'
    }, 80);

    items.add('editPosts', {
      icon: 'pencil',
      label: app.translator.trans('core.admin.permissions.edit_and_delete_posts_label'),
      permission: 'discussion.editPosts'
    }, 70);

    items.add('deletePosts', {
      icon: 'times',
      label: app.translator.trans('core.admin.permissions.delete_posts_forever_label'),
      permission: 'discussion.deletePosts'
    }, 60);

    return items;
  }

  scopeItems() {
    const items = new ItemList();

    items.add('global', {
      label: app.translator.trans('core.admin.permissions.global_heading'),
      render: item => {
        if (item.setting) {
          return item.setting();
        } else if (item.permission) {
          return PermissionDropdown.component({
            permission: item.permission,
            allowGuest: item.allowGuest
          });
        }

        return '';
      }
    }, 100);

    return items;
  }

  scopeControlItems() {
    return new ItemList();
  }
}
