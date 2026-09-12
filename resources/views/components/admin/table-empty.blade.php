@props([
    'colspan',
    'message',
])

<tr>
    <td colspan="{{ $colspan }}" class="text-center text-muted py-4">{{ $message }}</td>
</tr>
