@include('partials.admin.fields.text', [
    'name' => 'name',
    'label' => __('Name'),
    'value' => old('name', $user->name ?? ''),
    'required' => true,
    'autofocus' => true,
])

@include('partials.admin.fields.text', [
    'name' => 'job_title',
    'label' => __('Job title'),
    'value' => old('job_title', $user->job_title ?? ''),
    'placeholder' => __('e.g. Developer, QA Engineer, Marketing'),
])

@if(isset($user))
    @include('partials.admin.fields.text', [
        'name' => 'email',
        'label' => __('Email'),
        'value' => $user->email,
        'disabled' => true,
        'margin' => 'mb-0',
        'help' => __('Manage team membership and team lead roles on the Teams tab.'),
    ])
@else
    @include('partials.admin.fields.text', [
        'name' => 'email',
        'label' => __('Email'),
        'value' => old('email'),
        'required' => true,
    ])

    @include('partials.admin.fields.text', [
        'name' => 'password',
        'label' => __('Password'),
        'type' => 'password',
        'required' => true,
    ])

    @include('partials.admin.fields.text', [
        'name' => 'password_confirmation',
        'label' => __('Confirm password'),
        'type' => 'password',
        'required' => true,
        'margin' => 'mb-0',
        'help' => __('Assign teams and set team lead roles on the Teams tab.'),
    ])
@endif
