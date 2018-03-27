import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import icon from 'flarum/helpers/icon';

/**
 * Shows an alert if the user has not yet confirmed their email address.
 *
 * @param {ForumApp} app
 */
export default function alertEmailConfirmation(app) {
  const user = app.session.user;

  if (!user || user.isActivated()) return;

  const resendButton = Button.component({
    className: 'Button Button--link',
    children: app.translator.trans('core.forum.user_email_confirmation.resend_button'),
    onclick: function() {
      resendButton.props.loading = true;
      m.redraw();

      app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/send-confirmation',
      }).then(() => {
        resendButton.props.loading = false;
        resendButton.props.children = [icon('check'), ' ', app.translator.trans('core.forum.user_email_confirmation.sent_message')];
        resendButton.props.disabled = true;
        m.redraw();
      }).catch(() => {
        resendButton.props.loading = false;
        m.redraw();
      });
    }
  });

  class ContainedAlert extends Alert {
    view() {
      const vdom = super.view();

      vdom.children = [<div className="container">{vdom.children}</div>];

      return vdom;
    }
  }

  m.mount(
    $('<div/>').insertBefore('#content')[0],
    ContainedAlert.component({
      dismissible: false,
      children: app.translator.trans('core.forum.user_email_confirmation.alert_message', {email: <strong>{user.email()}</strong>}),
      controls: [resendButton]
    })
  );
}
