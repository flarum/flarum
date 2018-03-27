import SettingsModal from 'flarum/components/SettingsModal';

export default class AkismetSettingsModal extends SettingsModal {
  className() {
    return 'AkismetSettingsModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-akismet.admin.akismet_settings.title');
  }

  form() {
    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-akismet.admin.akismet_settings.api_key_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-akismet.api_key')}/>
      </div>
    ];
  }
}
