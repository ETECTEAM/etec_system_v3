<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Website\Requests\SchoolSettingRequest;
use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingController extends Controller
{
    public function __construct(
        private readonly WebsiteContentService $website,
    ) {}

    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/SchoolSettings', [
            'settings' => $this->website->publicSettings(),
        ]);
    }

    public function update(SchoolSettingRequest $request): RedirectResponse
    {
        $settings = $this->website->settings();
        $oldLogo = $settings->school_logo;
        $logoPath = $oldLogo;

        // FILE: disabled - not using file uploads
        // if ($request->hasFile('school_logo')) {
        //     $logoPath = $this->website->uniqueUploadPath($request->file('school_logo'), 'uploads/settings');
        // }

        $settings->update([
            'school_name' => $request->validated('school_name'),
            'school_logo' => $logoPath,
        ]);

        // FILE: disabled - not using file uploads
        // if ($request->hasFile('school_logo') && $oldLogo !== $logoPath) {
        //     $this->website->deletePublicFile($oldLogo);
        // }

        return back()->with('success', 'School settings updated successfully.');
    }

    // FILE: disabled - not using file uploads
    // public function removeLogo(Request $request): RedirectResponse
    // {
    //     abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);
    //
    //     $settings = $this->website->settings();
    //     $oldLogo = $settings->school_logo;
    //
    //     $settings->update(['school_logo' => null]);
    //     $this->website->deletePublicFile($oldLogo);
    //
    //     return back()->with('success', 'School logo removed successfully.');
    // }
}
