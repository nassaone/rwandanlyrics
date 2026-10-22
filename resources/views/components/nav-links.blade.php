@props(['block' => false])
@php($link = $block ? 'block py-3' : 'hover:text-accent-600')
<a href="{{ route('songs.index') }}" class="{{ $link }}">Lyrics</a>
<a href="{{ route('artists.index') }}" class="{{ $link }}">Artists</a>
<a href="{{ route('genres.index') }}" class="{{ $link }}">Genres</a>
@auth
    @if (auth()->user()->atLeast(\App\Enums\Role::Editor))
        <a href="{{ route('admin.dashboard') }}" class="{{ $link }}">Admin</a>
    @endif
    @if (auth()->user()->atLeast(\App\Enums\Role::Contributor))
        <a href="{{ route('contribute.index') }}" class="{{ $link }}">Contribute</a>
    @endif
    <a href="{{ route('account.edit') }}" class="{{ $link }}">Account</a>
    <form method="POST" action="{{ route('logout') }}" class="{{ $block ? '' : 'inline' }}">
        @csrf
        <button type="submit" class="{{ $link }} {{ $block ? 'w-full text-left' : '' }}">Log out</button>
    </form>
@else
    <a href="{{ route('login') }}" class="{{ $link }}">Log in</a>
    @if (config('rwandanlyrics.registration_enabled'))
        <a href="{{ route('register') }}" class="{{ $link }}">Register</a>
    @endif
@endauth
