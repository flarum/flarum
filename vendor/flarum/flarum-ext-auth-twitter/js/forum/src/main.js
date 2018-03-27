import { extend } from 'flarum/extend';
import app from 'flarum/app';
import LogInButtons from 'flarum/components/LogInButtons';
import LogInButton from 'flarum/components/LogInButton';

app.initializers.add('flarum-auth-twitter', () => {
  extend(LogInButtons.prototype, 'items', function(items) {
    items.add('twitter',
      <LogInButton
        className="Button LogInButton--twitter"
        icon="twitter"
        path="/auth/twitter">
        {app.translator.trans('flarum-auth-twitter.forum.log_in.with_twitter_button')}
      </LogInButton>
    );
  });
});
