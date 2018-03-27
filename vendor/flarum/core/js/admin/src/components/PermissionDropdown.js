import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';
import Separator from 'flarum/components/Separator';
import Group from 'flarum/models/Group';
import Badge from 'flarum/components/Badge';
import GroupBadge from 'flarum/components/GroupBadge';

function badgeForId(id) {
  const group = app.store.getById('groups', id);

  return group ? GroupBadge.component({group, label: null}) : '';
}

function filterByRequiredPermissions(groupIds, permission) {
  app.getRequiredPermissions(permission)
    .forEach(required => {
      const restrictToGroupIds = app.data.permissions[required] || [];

      if (restrictToGroupIds.indexOf(Group.GUEST_ID) !== -1) {
        // do nothing
      } else if (restrictToGroupIds.indexOf(Group.MEMBER_ID) !== -1) {
        groupIds = groupIds.filter(id => id !== Group.GUEST_ID);
      } else if (groupIds.indexOf(Group.MEMBER_ID) !== -1) {
        groupIds = restrictToGroupIds;
      } else {
        groupIds = restrictToGroupIds.filter(id => groupIds.indexOf(id) !== -1);
      }

      groupIds = filterByRequiredPermissions(groupIds, required);
    });

  return groupIds;
}

export default class PermissionDropdown extends Dropdown {
  static initProps(props) {
    super.initProps(props);

    props.className = 'PermissionDropdown';
    props.buttonClassName = 'Button Button--text';
  }

  view() {
    this.props.children = [];

    let groupIds = app.data.permissions[this.props.permission] || [];

    groupIds = filterByRequiredPermissions(groupIds, this.props.permission);

    const everyone = groupIds.indexOf(Group.GUEST_ID) !== -1;
    const members = groupIds.indexOf(Group.MEMBER_ID) !== -1;
    const adminGroup = app.store.getById('groups', Group.ADMINISTRATOR_ID);

    if (everyone) {
      this.props.label = Badge.component({icon: 'globe'});
    } else if (members) {
      this.props.label = Badge.component({icon: 'user'});
    } else {
      this.props.label = [
        badgeForId(Group.ADMINISTRATOR_ID),
        groupIds.map(badgeForId)
      ];
    }

    if (this.showing) {
      if (this.props.allowGuest) {
        this.props.children.push(
          Button.component({
            children: [Badge.component({icon: 'globe'}), ' ', app.translator.trans('core.admin.permissions_controls.everyone_button')],
            icon: everyone ? 'check' : true,
            onclick: () => this.save([Group.GUEST_ID]),
            disabled: this.isGroupDisabled(Group.GUEST_ID)
          })
        );
      }

      this.props.children.push(
        Button.component({
          children: [Badge.component({icon: 'user'}), ' ', app.translator.trans('core.admin.permissions_controls.members_button')],
          icon: members ? 'check' : true,
          onclick: () => this.save([Group.MEMBER_ID]),
          disabled: this.isGroupDisabled(Group.MEMBER_ID)
        }),

        Separator.component(),

        Button.component({
          children: [badgeForId(adminGroup.id()), ' ', adminGroup.namePlural()],
          icon: !everyone && !members ? 'check' : true,
          disabled: !everyone && !members,
          onclick: e => {
            if (e.shiftKey) e.stopPropagation();
            this.save([]);
          }
        })
      );

      [].push.apply(
        this.props.children,
        app.store.all('groups')
          .filter(group => [Group.ADMINISTRATOR_ID, Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
          .map(group => Button.component({
            children: [badgeForId(group.id()), ' ', group.namePlural()],
            icon: groupIds.indexOf(group.id()) !== -1 ? 'check' : true,
            onclick: (e) => {
              if (e.shiftKey) e.stopPropagation();
              this.toggle(group.id());
            },
            disabled: this.isGroupDisabled(group.id()) && this.isGroupDisabled(Group.MEMBER_ID) && this.isGroupDisabled(Group.GUEST_ID)
          }))
      );
    }

    return super.view();
  }

  save(groupIds) {
    const permission = this.props.permission;

    app.data.permissions[permission] = groupIds;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/permission',
      data: {permission, groupIds}
    });
  }

  toggle(groupId) {
    const permission = this.props.permission;

    let groupIds = app.data.permissions[permission] || [];

    const index = groupIds.indexOf(groupId);

    if (index !== -1) {
      groupIds.splice(index, 1);
    } else {
      groupIds.push(groupId);
      groupIds = groupIds.filter(id => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(id) === -1);
    }

    this.save(groupIds);
  }

  isGroupDisabled(id) {
    return filterByRequiredPermissions([id], this.props.permission).indexOf(id) === -1;
  }
}
