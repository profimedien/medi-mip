@props(['user'])
@if ($url = $user->avatarUrl())
    <img src="{{ $url }}" alt="" {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => 'grid shrink-0 place-items-center rounded-full bg-medi-blue/10 font-bold text-medi-blue']) }}>{{ $user->initials() }}</span>
@endif
