import Page from 'flarum/components/Page';
import GroupBadge from 'flarum/components/GroupBadge';
import EditGroupModal from 'flarum/components/EditGroupModal';
import Group from 'flarum/models/Group';
import icon from 'flarum/helpers/icon';
import PermissionGrid from 'flarum/components/PermissionGrid';

export default class PermissionsPage extends Page {
  view() {
    return (
      <div className="PermissionsPage">
        <div className="PermissionsPage-groups">
          <div className="container">
            {app.store.all('groups')
              .filter(group => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
              .map(group => (
                <button className="Button Group" onclick={() => app.modal.show(new EditGroupModal({group}))}>
                  {GroupBadge.component({
                    group,
                    className: 'Group-icon',
                    label: null
                  })}
                  <span className="Group-name">{group.namePlural()}</span>
                </button>
              ))}
            <button className="Button Group Group--add" onclick={() => app.modal.show(new EditGroupModal())}>
              {icon('plus', {className: 'Group-icon'})}
              <span className="Group-name">{app.translator.trans('core.admin.permissions.new_group_button')}</span>
            </button>
          </div>
        </div>

        <div className="PermissionsPage-permissions">
          <div className="container">
            {PermissionGrid.component()}
          </div>
        </div>
      </div>
    );
  }
}
