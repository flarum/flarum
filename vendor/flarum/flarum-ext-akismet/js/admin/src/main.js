import app from 'flarum/app';

import AkismetSettingsModal from 'flarum/akismet/components/AkismetSettingsModal';

app.initializers.add('flarum-akismet', () => {
  app.extensionSettings['flarum-akismet'] = () => app.modal.show(new AkismetSettingsModal());
});
