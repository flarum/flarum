import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import GroupBadge from 'flarum/components/GroupBadge';
import Group from 'flarum/models/Group';
import extractText from 'flarum/utils/extractText';

/**
 * The `EditUserModal` component displays a modal dialog with a login form.
 */
export default class EditUserModal extends Modal {
  init() {
    super.init();

    const user = this.props.user;

    this.username = m.prop(user.username() || '');
    this.email = m.prop(user.email() || '');
    this.isActivated = m.prop(user.isActivated() || false);
    this.setPassword = m.prop(false);
    this.password = m.prop(user.password() || '');
    this.groups = {};

    app.store.all('groups')
      .filter(group => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
      .forEach(group => this.groups[group.id()] = m.prop(user.groups().indexOf(group) !== -1));
  }

  className() {
    return 'EditUserModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.edit_user.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('core.forum.edit_user.username_heading')}</label>
            <input className="FormControl" placeholder={extractText(app.translator.trans('core.forum.edit_user.username_label'))}
              bidi={this.username} />
          </div>

          {app.session.user !== this.props.user ? [
            <div className="Form-group">
              <label>{app.translator.trans('core.forum.edit_user.email_heading')}</label>
              <div>
                <input className="FormControl" placeholder={extractText(app.translator.trans('core.forum.edit_user.email_label'))}
                  bidi={this.email} />
              </div>
              {!this.isActivated() ? (
                <div>
                  {Button.component({
                    className: 'Button Button--block',
                    children: app.translator.trans('core.forum.edit_user.activate_button'),
                    loading: this.loading,
                    onclick: this.activate.bind(this)
                  })}
                </div>
              ) : ''}
            </div>,

            <div className="Form-group">
              <label>{app.translator.trans('core.forum.edit_user.password_heading')}</label>
              <div>
                <label className="checkbox">
                  <input type="checkbox" checked={this.setPassword()} onchange={e => {
                    this.setPassword(e.target.checked);
                    m.redraw(true);
                    if (e.target.checked) this.$('[name=password]').select();
                    m.redraw.strategy('none');
                  }}/>
                  {app.translator.trans('core.forum.edit_user.set_password_label')}
                </label>
                {this.setPassword() ? (
                  <input className="FormControl" type="password" name="password" placeholder={extractText(app.translator.trans('core.forum.edit_user.password_label'))}
                    bidi={this.password} />
                ) : ''}
              </div>
            </div>
          ] : ''}

          <div className="Form-group EditUserModal-groups">
            <label>{app.translator.trans('core.forum.edit_user.groups_heading')}</label>
            <div>
              {Object.keys(this.groups)
                .map(id => app.store.getById('groups', id))
                .map(group => (
                  <label className="checkbox">
                    <input type="checkbox"
                      bidi={this.groups[group.id()]}
                      disabled={this.props.user.id() === '1' && group.id() === Group.ADMINISTRATOR_ID} />
                    {GroupBadge.component({group, label: ''})} {group.nameSingular()}
                  </label>
                ))}
            </div>
          </div>

          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.edit_user.submit_button')
            })}
          </div>
        </div>
      </div>
    );
  }

  activate() {
    this.loading = true;
    const data = {
      username: this.username(),
      isActivated: true,
    };
    this.props.user.save(data, {errorHandler: this.onerror.bind(this)})
      .then(() => {
        this.isActivated(true);
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  data() {
    const groups = Object.keys(this.groups)
      .filter(id => this.groups[id]())
      .map(id => app.store.getById('groups', id));

    const data = {
      username: this.username(),
      relationships: {groups}
    };

    if (app.session.user !== this.props.user) {
      data.email = this.email();
    }

    if (this.setPassword()) {
      data.password = this.password();
    }

    return data;
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    this.props.user.save(this.data(), {errorHandler: this.onerror.bind(this)})
      .then(this.hide.bind(this))
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
