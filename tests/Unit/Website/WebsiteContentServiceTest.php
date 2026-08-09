<?php

namespace Tests\Unit\Website;

use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteContentServiceTest extends TestCase
{
    public function test_public_image_data_uri_converts_public_image_to_base64_data_uri(): void
    {
        Storage::fake('public');

        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');
        Storage::disk('public')->put('uploads/settings/logo.png', $image);

        $dataUri = app(WebsiteContentService::class)->publicImageDataUri('uploads/settings/logo.png');

        $this->assertIsString($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        $this->assertStringNotContainsString('/storage/', $dataUri);
    }

    public function test_public_image_data_uri_returns_null_for_missing_local_file(): void
    {
        Storage::fake('public');

        $this->assertNull(app(WebsiteContentService::class)->publicImageDataUri('uploads/missing.png'));
    }
}
