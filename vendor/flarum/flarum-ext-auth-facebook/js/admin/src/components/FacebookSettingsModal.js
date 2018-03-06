import SettingsModal from 'flarum/components/SettingsModal';

export default class FacebookSettingsModal extends SettingsModal {
  className() {
    return 'FacebookSettingsModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-auth-facebook.admin.facebook_settings.title');
  }

  form() {
    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-auth-facebook.admin.facebook_settings.app_id_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-auth-facebook.app_id')}/>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('flarum-auth-facebook.admin.facebook_settings.app_secret_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-auth-facebook.app_secret')}/>
      </div>
    ];
  }
}
