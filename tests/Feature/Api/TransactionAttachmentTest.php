<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * OWF-284: wiring real del campo "Foto / soporte" — sube el archivo al disco
 * 'public' (attachments/{user_id}/...) y devuelve la URL pública para guardar
 * en transactions.url_file.
 */
class TransactionAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_attachment_stores_file_and_returns_url()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('receipt.jpg', 200, 200)->size(100);

        $response = $this->postJson('/api/v1/transactions/attachments', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'OK']);

        $url = $response->json('data.url_file');
        $this->assertNotEmpty($url);
        $this->assertStringContainsString('/storage/attachments/' . $this->authUser->id . '/', $url);

        $path = 'attachments/' . $this->authUser->id . '/' . basename(parse_url($url, PHP_URL_PATH));
        Storage::disk('public')->assertExists($path);
    }

    public function test_upload_attachment_rejects_disallowed_mime_type()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->postJson('/api/v1/transactions/attachments', [
            'file' => $file,
        ]);

        $response->assertStatus(400);
        $this->assertArrayHasKey('file', $response->json('data'));
    }

    public function test_upload_attachment_rejects_oversized_file()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('receipt.jpg')->size(6000);

        $response = $this->postJson('/api/v1/transactions/attachments', [
            'file' => $file,
        ]);

        $response->assertStatus(400);
        $this->assertArrayHasKey('file', $response->json('data'));
    }

    public function test_upload_attachment_requires_authentication()
    {
        Storage::fake('public');
        $this->app['auth']->forgetGuards();

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->postJson('/api/v1/transactions/attachments', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }
}
