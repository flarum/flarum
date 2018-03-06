Hey {{ $user->username }}!

{{ $blueprint->reply->user->username }} replied to your post (#{{ $blueprint->post->number }}) in {{ $blueprint->post->discussion->title }}.

{{ app()->url() }}/d/{{ $blueprint->reply->discussion_id }}/{{ $blueprint->reply->number }}

---

{{ strip_tags($blueprint->reply->contentHtml) }}
