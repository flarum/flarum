import { extend } from 'flarum/extend';
import app from 'flarum/app';

import PusherSettingsModal from 'flarum/pusher/components/PusherSettingsModal';

app.initializers.add('flarum-pusher', app => {
  app.extensionSettings['flarum-pusher'] = () => app.modal.show(new PusherSettingsModal());
});
