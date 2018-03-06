import app from 'flarum/app';

import GithubSettingsModal from 'flarum/auth/github/components/GithubSettingsModal';

app.initializers.add('flarum-auth-github', () => {
  app.extensionSettings['flarum-auth-github'] = () => app.modal.show(new GithubSettingsModal());
});
