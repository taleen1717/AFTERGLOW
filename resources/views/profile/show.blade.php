@extends('Layout.app')
@section('title', 'My Profile — AFTERGLOW')
@section('content')
<link rel="stylesheet" href="{{ asset('afterglow/social-v2.css') }}?v=2">
<section class="ag-v2-shell">
    <header class="ag-v2-hero">
        <p class="ag-v2-kicker">AFTERGLOW / MY ACCOUNT</p>
        <h1>My <em>Profile</em> ✦</h1>
        <p>Make this little corner of the universe yours. Switch between Cyber Night and Icy Ethereal using the theme toggle above.</p>
        <a href="{{ route('home') }}" class="ag-v2-back">← Back to AFTERGLOW</a>
    </header>
    @if(session('profile_success'))<div class="ag-v2-success" role="status">✓ {{ session('profile_success') }}</div>@endif
    @if($errors->any())<div class="ag-v2-error" role="alert"><strong>Please check:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="ag-v2-profile-grid">
        <article class="ag-v2-panel ag-v2-avatar-panel">
            <h2>Your avatar</h2>
            <img class="ag-v2-avatar" src="{{ $user->avatar_path ? route('users.avatar', $user) : asset('images/profile.jpeg') }}" alt="Your current profile picture">
            <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" class="ag-v2-form">
                @csrf
                <label>Choose a new photo (JPG, PNG, WEBP or GIF, max 2 MB)<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
                <button type="submit">Upload avatar</button>
            </form>
            @if($user->avatar_path)
                <form method="POST" action="{{ route('profile.avatar.remove') }}">@csrf @method('DELETE')<button class="ag-v2-danger" type="submit">Remove avatar</button></form>
            @endif
        </article>
        <article class="ag-v2-panel">
            <h2>Account details</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="ag-v2-form">
                @csrf @method('PATCH')
                <label>Display name<input name="name" minlength="2" maxlength="50" required value="{{ old('name', $user->name) }}"></label>
                <label>Email address<input type="email" name="email" required maxlength="255" value="{{ old('email', $user->email) }}"></label>
                <label>About me <span>(optional)</span><textarea name="bio" maxlength="280" rows="4" placeholder="A little about your universe...">{{ old('bio', $user->bio) }}</textarea></label>
                <button type="submit">Save profile changes</button>
            </form>
        </article>
        <article class="ag-v2-panel">
            <h2>Change password</h2>
            <p class="ag-v2-hint">Enter your current password to keep your account secure.</p>
            <form method="POST" action="{{ route('profile.password') }}" class="ag-v2-form">
                @csrf @method('PATCH')
                <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>New password<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
                <label>Confirm new password<input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></label>
                <button type="submit">Update password</button>
            </form>
        </article>
    </div>
</section>
@endsection
