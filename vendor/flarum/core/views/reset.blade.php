<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Your Password</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">
  </head>

  <body>
    <h2>{{ $translator->trans('core.views.reset.title') }}</h2>

    @if (! empty($error))
      <p style="color:red">{{ $error }}</p>
    @endif

    <form class="form-horizontal" role="form" method="POST" action="{{ app('Flarum\Forum\UrlGenerator')->toRoute('savePassword') }}">
      <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
      <input type="hidden" name="passwordToken" value="{{ $passwordToken }}">

      <p class="form-group">
        <label class="control-label">{{ $translator->trans('core.views.reset.password_label') }}</label><br>
        <input type="password" class="form-control" name="password">
      </p>

      <p class="form-group">
        <label class="control-label">{{ $translator->trans('core.views.reset.confirm_password_label') }}</label><br>
        <input type="password" class="form-control" name="password_confirmation">
      </p>

      <p class="form-group">
        <button type="submit" class="btn btn-primary">{{ $translator->trans('core.views.reset.submit_button') }}</button>
      </p>
    </form>
  </body>
</html>
