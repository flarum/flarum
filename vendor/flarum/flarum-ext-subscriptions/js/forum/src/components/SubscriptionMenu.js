import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';
import icon from 'flarum/helpers/icon';
import extractText from 'flarum/utils/extractText';

import SubscriptionMenuItem from 'flarum/subscriptions/components/SubscriptionMenuItem';

export default class SubscriptionMenu extends Dropdown {
  init() {
    this.options = [
      {
        subscription: false,
        icon: 'star-o',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_text')
      },
      {
        subscription: 'follow',
        icon: 'star',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_text')
      },
      {
        subscription: 'ignore',
        icon: 'eye-slash',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_text')
      }
    ];
  }

  view() {
    const discussion = this.props.discussion;
    const subscription = discussion.subscription();

    let buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.follow_button');
    let buttonIcon = 'star-o';
    const buttonClass = 'SubscriptionMenu-button--' + subscription;

    switch (subscription) {
      case 'follow':
        buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button');
        buttonIcon = 'star';
        break;

      case 'ignore':
        buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button');
        buttonIcon = 'eye-slash';
        break;

      default:
        // no default
    }

    const preferences = app.session.user.preferences();
    const notifyEmail = preferences['notify_newPost_email'];
    const notifyAlert = preferences['notify_newPost_alert'];
    const title = extractText(app.translator.trans(notifyEmail
      ? 'flarum-subscriptions.forum.sub_controls.notify_email_tooltip'
      : 'flarum-subscriptions.forum.sub_controls.notify_alert_tooltip'));

    const buttonProps = {
      className: 'Button SubscriptionMenu-button ' + buttonClass,
      icon: buttonIcon,
      children: buttonLabel,
      onclick: this.saveSubscription.bind(this, discussion, ['follow', 'ignore'].indexOf(subscription) !== -1 ? false : 'follow'),
      title: title
    };

    if ((notifyEmail || notifyAlert) && subscription === false) {
      buttonProps.config = element => {
        $(element).tooltip({
          container: '.SubscriptionMenu',
          placement: 'bottom',
          delay: 250,
          title
        });
      }
    } else {
      buttonProps.config = element => $(element).tooltip('destroy');
    }

    return (
      <div className="Dropdown ButtonGroup SubscriptionMenu">
        {Button.component(buttonProps)}

        <button className={'Dropdown-toggle Button Button--icon ' + buttonClass} data-toggle="dropdown">
          {icon('caret-down', {className: 'Button-icon'})}
        </button>

        <ul className="Dropdown-menu dropdown-menu Dropdown-menu--right">
          {this.options.map(props => {
            props.onclick = this.saveSubscription.bind(this, discussion, props.subscription);
            props.active = subscription === props.subscription;

            return <li>{SubscriptionMenuItem.component(props)}</li>;
          })}
        </ul>
      </div>
    );
  }

  saveSubscription(discussion, subscription) {
    discussion.save({subscription});

    this.$('.SubscriptionMenu-button').tooltip('hide');
  }
}
