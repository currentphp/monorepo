<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ExperienceData;
use App\Livewire\OnboardingPage;
use App\OnboardingDemo;
use App\Rendering;
use Current\Facades\Current;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OnboardingController extends Controller
{
    public function show(Request $request, OnboardingDemo $demo): Response|View|HttpResponse
    {
        $rendering = Rendering::forRequest($request);
        $pageId = Current::pageId() ?? 'onboarding';
        if ($rendering === Rendering::Livewire) {
            return app(OnboardingPage::class)();
        }
        $data = $demo->data($pageId, $rendering);
        if ($rendering === Rendering::Json || $request->expectsJson()) {
            return response()->json([...$data, 'current' => ExperienceData::from($demo->experience($pageId))]);
        }

        return $rendering === Rendering::Blade
            ? view('onboarding.page', [...$data, 'experience' => $demo->experience($pageId)])
            : Inertia::render('onboarding', $data);
    }

    public function profile(Request $request, OnboardingDemo $demo): HttpResponse
    {
        $demo->saveProfile($request->all());

        return redirect()->to(Rendering::forRequest($request)->url(route('onboarding.workspace')));
    }

    public function workspace(Request $request, OnboardingDemo $demo): HttpResponse
    {
        $demo->saveWorkspace($request->all());

        return redirect()->to(Rendering::forRequest($request)->url(route('onboarding.review', 'demo')));
    }

    public function finish(Request $request, OnboardingDemo $demo): HttpResponse
    {
        $demo->finish();

        return redirect()->to(Rendering::forRequest($request)->url(route('onboarding.review', 'demo')))->with('status', 'Simulation complete. No account was created.');
    }

    public function reset(Request $request, OnboardingDemo $demo): HttpResponse
    {
        $demo->reset();

        return redirect()->to(Rendering::forRequest($request)->url(route('onboarding.index')))->with('status', 'Onboarding simulation reset.');
    }
}
