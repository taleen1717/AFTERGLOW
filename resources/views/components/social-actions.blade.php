@props(['item', 'kind'])
@php
    $kinds = [
        'quote' => [
            'like' => 'quotes.like', 'comment' => 'quotes.comments.store',
            'delete' => 'quotes.comments.destroy', 'prefix' => 'quote',
        ],
        'board' => [
            'like' => 'boards.like', 'comment' => 'boards.comments.store',
            'delete' => 'boards.comments.destroy', 'prefix' => 'board',
        ],
        'code' => [
            'like' => 'code-exchange.like', 'comment' => 'code-exchange.comments.store',
            'delete' => 'code-exchange.comments.destroy', 'prefix' => 'code',
        ],
        'song' => [
            'like' => 'soundtrack.public.like', 'comment' => 'soundtrack.public.comments.store',
            'delete' => 'soundtrack.public.comments.destroy', 'prefix' => 'song',
        ],
    ];
    $config = $kinds[$kind] ?? null;
    if ($config === null) {
        throw new InvalidArgumentException('Unsupported social content type.');
    }
    $likeRoute = route($config['like'], $item);
    $commentRoute = route($config['comment'], $item);
@endphp
<div class="ag-social" id="{{ $config['prefix'] }}-social-{{ $item->id }}">
    <div class="ag-social-summary">
        @auth
            <form method="POST" action="{{ $likeRoute }}">
                @csrf
                <button type="submit" class="ag-social-like {{ $item->liked_by_me ? 'is-liked' : '' }}" aria-label="{{ $item->liked_by_me ? 'Unlike' : 'Like' }}">
                    {{ $item->liked_by_me ? '♥' : '♡' }} {{ $item->likes_count }} Likes
                </button>
            </form>
        @else
            <span class="ag-social-count">♡ {{ $item->likes_count }} Likes</span>
        @endauth
        <span class="ag-social-count">◌ {{ $item->comments_count }} Comments</span>
    </div>
    <details class="ag-social-comments">
        <summary>View comments ({{ $item->comments_count }})</summary>
        <div class="ag-social-thread">
            @forelse ($item->comments->take(5) as $comment)
                <div class="ag-social-comment">
                    <img class="ag-social-comment-avatar"
                        src="{{ $comment->user?->avatar_path ? route('users.avatar', $comment->user) : asset('images/profile.jpeg') }}"
                        alt="" loading="lazy">
                    <div class="ag-social-comment-content">
                        <strong>{{ $comment->user?->name ?? 'Community member' }}</strong>
                        <p>{{ $comment->body }}</p>
                    </div>
                    @auth
                        @if ((int) $comment->user_id === (int) auth()->id())
                            <form method="POST" action="{{ route($config['delete'], $comment) }}" onsubmit="return confirm('Delete your comment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ag-social-comment-delete" aria-label="Delete comment">×</button>
                            </form>
                        @endif
                    @endauth
                </div>
            @empty
                <p class="ag-social-muted">No comments yet. Start the conversation!</p>
            @endforelse
            @if ($item->comments_count > 5)
                <p class="ag-social-muted">Showing the latest 5 comments.</p>
            @endif
        </div>
    </details>
    @auth
        <form method="POST" action="{{ $commentRoute }}" class="ag-social-form">
            @csrf
            <input type="text" name="body" maxlength="500" required placeholder="Write a kind comment..." aria-label="Write a comment">
            <button type="submit">Post</button>
        </form>
    @else
        <a class="ag-social-login" href="{{ route('login') }}">Log in to like or comment ↗</a>
    @endauth
</div>
