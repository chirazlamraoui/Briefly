@props([
    'headers' => [],
])

<table class="table table-hover mb-0 align-middle">
    <thead>
        <tr>
            @foreach($headers as $header)
                <th @if($loop->last && $header === '') class="text-end" @endif>{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        {{ $slot }}
    </tbody>
</table>
