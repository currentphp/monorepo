@component('experience.layout', ['title' => $experience->title(), 'rendering' => \App\Rendering::Blade])
    <div data-example-content>
        @include('experience.feedback')
        @include('experience.breadcrumbs', ['rendering' => \App\Rendering::Blade])
        @include('onboarding.panel', ['rendering' => \App\Rendering::Blade])
    </div>
@endcomponent
