import app from 'flarum/app';

import FacebookSettingsModal from 'flarum/auth/facebook/components/FacebookSettingsModal';

app.initializers.add('flarum-auth-facebook', () => {
  app.extensionSettings['flarum-auth-facebook'] = () => app.modal.show(new FacebookSettingsModal());
});
