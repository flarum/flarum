import ForumApp from 'flarum/ForumApp';
import store from 'flarum/initializers/store';
import preload from 'flarum/initializers/preload';
import routes from 'flarum/initializers/routes';
import components from 'flarum/initializers/components';
import humanTime from 'flarum/initializers/humanTime';
import boot from 'flarum/initializers/boot';
import alertEmailConfirmation from 'flarum/initializers/alertEmailConfirmation';

const app = new ForumApp();

app.initializers.add('store', store);
app.initializers.add('routes', routes);
app.initializers.add('components', components);
app.initializers.add('humanTime', humanTime);

app.initializers.add('preload', preload, -100);
app.initializers.add('boot', boot, -100);
app.initializers.add('alertEmailConfirmation', alertEmailConfirmation, -100);

export default app;
