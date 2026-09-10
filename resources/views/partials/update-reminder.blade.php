@if($updateReminder)
    <div class="alert alert-{{ $updateReminder['past'] ? 'danger' : 'warning' }} d-flex align-items-start gap-2 mb-4" role="alert">
        <i class="bi bi-{{ $updateReminder['past'] ? 'exclamation-octagon' : 'clock' }} mt-1"></i>
        <div>
            @if($updateReminder['past'])
                <strong>{{ __('Daily update missing') }}</strong>
                <div class="small">{{ __('The submission deadline (:time, :timezone) has passed.', [
                    'time' => $updateReminder['deadline_label'],
                    'timezone' => $updateReminder['timezone'],
                ]) }}</div>
            @else
                <strong>{{ __('Reminder: submit your daily update') }}</strong>
                <div class="small">{{ __('Please submit before :time (:timezone).', [
                    'time' => $updateReminder['deadline_label'],
                    'timezone' => $updateReminder['timezone'],
                ]) }}</div>
            @endif
            <a href="{{ route('daily-update.edit') }}" class="alert-link small">{{ __('Submit now') }}</a>
        </div>
    </div>
@endif
