import Modal from 'flarum/components/Modal';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import extractText from 'flarum/utils/extractText';

/**
 * The `ForgotPasswordModal` component displays a modal which allows the user to
 * enter their email address and request a link to reset their password.
 *
 * ### Props
 *
 * - `email`
 */
export default class ForgotPasswordModal extends Modal {
  init() {
    super.init();

    /**
     * The value of the email input.
     *
     * @type {Function}
     */
    this.email = m.prop(this.props.email || '');

    /**
     * Whether or not the password reset email was sent successfully.
     *
     * @type {Boolean}
     */
    this.success = false;
  }

  className() {
    return 'ForgotPasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.forgot_password.title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('core.forum.forgot_password.email_sent_message')}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('core.forum.forgot_password.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{app.translator.trans('core.forum.forgot_password.text')}</p>
          <div className="Form-group">
            <input className="FormControl" name="email" type="email" placeholder={extractText(app.translator.trans('core.forum.forgot_password.email_placeholder'))}
              value={this.email()}
              onchange={m.withAttr('value', this.email)}
              disabled={this.loading} />
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.forgot_password.submit_button')
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
      data: {email: this.email()},
      errorHandler: this.onerror.bind(this)
    })
      .then(() => {
        this.success = true;
        this.alert = null;
      })
      .catch(() => {})
      .then(this.loaded.bind(this));
  }

  onerror(error) {
    if (error.status === 404) {
      error.alert.props.children = app.translator.trans('core.forum.forgot_password.not_found_message');
    }

    super.onerror(error);
  }
}
