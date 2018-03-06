import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
export default class ChangePasswordModal extends Modal {
  className() {
    return 'ChangePasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.change_password.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{app.translator.trans('core.forum.change_password.text')}</p>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.change_password.send_button')
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/forgot',
      data: {email: app.session.user.email()}
    }).then(
      this.hide.bind(this),
      this.loaded.bind(this)
    );
  }
}
