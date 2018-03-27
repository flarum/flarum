import SettingsModal from 'flarum/components/SettingsModal';

export default class TwitterSettingsModal extends SettingsModal {
  className() {
    return 'TwitterSettingsModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-auth-twitter.admin.twitter_settings.title');
  }

  form() {
    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-auth-twitter.admin.twitter_settings.api_key_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-auth-twitter.api_key')}/>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('flarum-auth-twitter.admin.twitter_settings.api_secret_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-auth-twitter.api_secret')}/>
      </div>
    ];
  }
}
