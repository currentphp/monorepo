<div data-example-content>
    @include('experience.feedback')
    @include('experience.breadcrumbs', ['rendering' => \App\Rendering::Livewire])
    @include('onboarding.panel', ['rendering' => \App\Rendering::Livewire, 'live' => true])
</div>
