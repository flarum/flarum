<div id="flarum-loading" style="display: none">
  {{ $translator->trans('core.views.content.loading_text') }}
</div>

@if ($allowJs)
  <noscript>
    <div class="Alert">
      <div class="container">
        {{ $translator->trans('core.views.content.javascript_disabled_message') }}
      </div>
    </div>

    {!! $content !!}
  </noscript>
@else
  <div class="Alert Alert--error">
    <div class="container">
      {{ $translator->trans('core.views.content.load_error_message') }}
    </div>
  </div>

  {!! $content !!}
@endif
