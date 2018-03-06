import SettingsModal from 'flarum/components/SettingsModal';

export default class TagSettingsModal extends SettingsModal {
  setMinTags(minTags, maxTags, value) {
    minTags(value);
    maxTags(Math.max(value, maxTags()));
  }

  className() {
    return 'TagSettingsModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-tags.admin.tag_settings.title');
  }

  form() {
    const minPrimaryTags = this.setting('flarum-tags.min_primary_tags', 0);
    const maxPrimaryTags = this.setting('flarum-tags.max_primary_tags', 0);

    const minSecondaryTags = this.setting('flarum-tags.min_secondary_tags', 0);
    const maxSecondaryTags = this.setting('flarum-tags.max_secondary_tags', 0);

    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.tag_settings.required_primary_heading')}</label>
        <div className="helpText">
          {app.translator.trans('flarum-tags.admin.tag_settings.required_primary_text')}
        </div>
        <div className="TagSettingsModal-rangeInput">
          <input className="FormControl"
            type="number"
            min="0"
            value={minPrimaryTags()}
            oninput={m.withAttr('value', this.setMinTags.bind(this, minPrimaryTags, maxPrimaryTags))} />
          {app.translator.trans('flarum-tags.admin.tag_settings.range_separator_text')}
          <input className="FormControl"
            type="number"
            min={minPrimaryTags()}
            bidi={maxPrimaryTags} />
        </div>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_heading')}</label>
        <div className="helpText">
          {app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_text')}
        </div>
        <div className="TagSettingsModal-rangeInput">
          <input className="FormControl"
            type="number"
            min="0"
            value={minSecondaryTags()}
            oninput={m.withAttr('value', this.setMinTags.bind(this, minSecondaryTags, maxSecondaryTags))} />
          {app.translator.trans('flarum-tags.admin.tag_settings.range_separator_text')}
          <input className="FormControl"
            type="number"
            min={minSecondaryTags()}
            bidi={maxSecondaryTags} />
        </div>
      </div>
    ];
  }
}
