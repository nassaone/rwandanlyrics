<?php

namespace Tests\Feature\Admin;

use App\Models\Artist;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::where('email', $role.'@example.test')->first() ?? User::forceCreate([
            'name' => ucfirst($role), 'email' => $role.'@example.test',
            'password' => bcrypt('password-123'), 'role' => $role,
        ]);
    }

    private function artist(): Artist
    {
        return Artist::forceCreate([
            'name' => 'Test Artist', 'slug' => 'test-artist', 'status' => 'published',
            'published_at' => now()->subDay(), 'verification_status' => 'unverified',
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Test Artist', 'slug' => 'test-artist', 'status' => 'published',
            'verification_status' => 'unverified',
        ], $extra);
    }

    public function test_editor_can_upload_an_image_with_alt_text(): void
    {
        Storage::fake('public');
        $artist = $this->artist();

        $this->actingAs($this->user('editor'))
            ->put(route('admin.artists.update', $artist), $this->payload([
                'image' => FakeImage::upload(), 'image_alt' => 'Portrait of the artist',
            ]))->assertSessionHasNoErrors();

        $media = Media::first();
        $this->assertNotNull($media);
        $this->assertSame('Portrait of the artist', $media->alt_text);
        $this->assertSame($media->id, $artist->fresh()->image_id);
        $this->assertNotEmpty(Storage::disk('public')->allFiles());
    }

    public function test_alt_text_is_required_with_an_upload(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user('editor'))
            ->put(route('admin.artists.update', $this->artist()), $this->payload(['image' => FakeImage::upload()]))
            ->assertSessionHasErrors('image_alt');

        $this->assertSame(0, Media::count());
    }

    public function test_a_non_image_with_an_image_name_is_rejected(): void
    {
        Storage::fake('public');
        $fake = UploadedFile::fake()->createWithContent('evil.png', '<?php echo "hi";');

        $this->actingAs($this->user('editor'))
            ->put(route('admin.artists.update', $this->artist()), $this->payload(['image' => $fake, 'image_alt' => 'x']))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Media::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_images_under_200_pixels_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user('editor'))
            ->put(route('admin.artists.update', $this->artist()), $this->payload([
                'image' => FakeImage::upload('small.png', 100, 100), 'image_alt' => 'Small',
            ]))->assertSessionHasErrors('image');
    }

    public function test_deleting_media_clears_references_and_is_admin_only(): void
    {
        Storage::fake('public');
        $artist = $this->artist();
        $this->actingAs($this->user('editor'))
            ->put(route('admin.artists.update', $artist), $this->payload([
                'image' => FakeImage::upload(), 'image_alt' => 'Portrait',
            ]));
        $media = Media::firstOrFail();

        $this->actingAs($this->user('editor'))->delete(route('admin.media.destroy', $media))->assertForbidden();

        $this->actingAs($this->user('admin'))->delete(route('admin.media.destroy', $media))->assertRedirect();
        $this->assertSame(0, Media::count());
        $this->assertNull($artist->fresh()->image_id);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_media_library_is_visible_to_editors(): void
    {
        $this->actingAs($this->user('editor'))->get(route('admin.media.index'))->assertOk();
    }
}
