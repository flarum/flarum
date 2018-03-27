import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

/**
 * The `ChangeEmailModal` component shows a modal dialog which allows the user
 * to change their email address.
 */
export default class ChangeEmailModal extends Modal {
  init() {
    super.init();

    /**
     * Whether or not the email has been changed successfully.
     *
     * @type {Boolean}
     */
    this.success = false;

    /**
     * The value of the email input.
     *
     * @type {function}
     */
    this.email = m.prop(app.session.user.email());

    /**
     * The value of the password input.
     *
     * @type {function}
     */
    this.password = m.prop('');
  }

  className() {
    return 'ChangeEmailModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.change_email.title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('core.forum.change_email.confirmation_message', {email: <strong>{this.email()}</strong>})}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('core.forum.change_email.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input type="email" name="email" className="FormControl"
              placeholder={app.session.user.email()}
              bidi={this.email}
              disabled={this.loading}/>
          </div>
          <div className="Form-group">
            <input type="password" name="password" className="FormControl"
              placeholder={app.translator.trans('core.forum.change_email.confirm_password_placeholder')}
              bidi={this.password}
              disabled={this.loading}/>
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.change_email.submit_button')
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    // If the user hasn't actually entered a different email address, we don't
    // need to do anything. Woot!
    if (this.email() === app.session.user.email()) {
      this.hide();
      return;
    }

    const oldEmail = app.session.user.email();

    this.loading = true;

    app.session.user.save({email: this.email()}, {
      errorHandler: this.onerror.bind(this),
      meta: {password: this.password()}
    })
      .then(() => this.success = true)
      .catch(() => {})
      .then(this.loaded.bind(this));
  }

  onerror(error) {
    if (error.status === 401) {
      error.alert.props.children = app.translator.trans('core.forum.change_email.incorrect_password_message');
    }

    super.onerror(error);
  }
}
