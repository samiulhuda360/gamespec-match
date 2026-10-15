@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'notice notice-ok']) }}>{{ $status }}</div>
@endif
