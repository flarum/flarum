import { extend } from 'flarum/extend';
import app from 'flarum/app';
import LogInButtons from 'flarum/components/LogInButtons';
import LogInButton from 'flarum/components/LogInButton';

app.initializers.add('flarum-auth-facebook', () => {
  extend(LogInButtons.prototype, 'items', function(items) {
    items.add('facebook',
      <LogInButton
        className="Button LogInButton--facebook"
        icon="facebook-official"
        path="/auth/facebook">
        {app.translator.trans('flarum-auth-facebook.forum.log_in.with_facebook_button')}
      </LogInButton>
    );
  });
});
