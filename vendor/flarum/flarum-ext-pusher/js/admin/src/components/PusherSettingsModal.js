import SettingsModal from 'flarum/components/SettingsModal';

export default class PusherSettingsModal extends SettingsModal {
  className() {
    return 'PusherSettingsModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-pusher.admin.pusher_settings.title');
  }

  form() {
    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-pusher.admin.pusher_settings.app_id_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-pusher.app_id')}/>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('flarum-pusher.admin.pusher_settings.app_key_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-pusher.app_key')}/>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('flarum-pusher.admin.pusher_settings.app_secret_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-pusher.app_secret')}/>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('flarum-pusher.admin.pusher_settings.app_cluster_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-pusher.app_cluster')}/>
      </div>
    ];
  }
}
