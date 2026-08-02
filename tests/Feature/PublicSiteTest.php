<?php

namespace Tests\Feature;

use App\Livewire\Website\WebsiteManager;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Institute;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    private Branch $published;

    private Course $publishedCourse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->institute = Institute::create(['name' => 'Acme Coaching']);
        AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);

        $this->published = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Vijay Nagar', 'code' => 'VN', 'slug' => 'vijay-nagar', 'city' => 'Indore', 'is_published' => true]);
        Branch::create(['institute_id' => $this->institute->id, 'name' => 'Draft Centre', 'code' => 'DC', 'slug' => 'draft-centre', 'is_published' => false]);

        $this->publishedCourse = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE Foundation', 'code' => 'JEE', 'slug' => 'jee-foundation', 'is_published' => true]);
        Course::create(['institute_id' => $this->institute->id, 'name' => 'Hidden Course', 'code' => 'HID', 'slug' => 'hidden', 'is_published' => false]);
    }

    public function test_home_shows_published_and_hides_unpublished(): void
    {
        $this->get('/site')->assertOk()
            ->assertSee('Vijay Nagar')
            ->assertSee('JEE Foundation')
            ->assertDontSee('Draft Centre')
            ->assertDontSee('Hidden Course');
    }

    public function test_branch_detail_404s_when_unpublished(): void
    {
        $this->get('/site/branches/vijay-nagar')->assertOk()->assertSee('Vijay Nagar');
        $this->get('/site/branches/draft-centre')->assertNotFound();
    }

    public function test_course_detail_renders_when_published(): void
    {
        $this->get('/site/courses/jee-foundation')->assertOk()->assertSee('JEE Foundation');
        $this->get('/site/courses/hidden')->assertNotFound();
    }

    public function test_enquiry_submission_creates_an_online_lead(): void
    {
        $this->post('/site/enquiry', [
            'name' => 'Riya Verma',
            'phone' => '9876543210',
            'email' => 'riya@example.com',
            'branch_id' => $this->published->id,
            'course_id' => $this->publishedCourse->id,
        ])->assertRedirect();

        $enquiry = Enquiry::withoutGlobalScopes()->where('name', 'Riya Verma')->first();
        $this->assertNotNull($enquiry);
        $this->assertSame('online', $enquiry->source);
        $this->assertSame($this->published->id, $enquiry->branch_id);
        $this->assertNotNull($enquiry->enquiry_number);
    }

    public function test_enquiry_rejects_unpublished_branch(): void
    {
        $draft = Branch::where('code', 'DC')->first();
        $this->post('/site/enquiry', [
            'name' => 'Spam Lead', 'phone' => '9000000000', 'branch_id' => $draft->id,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('enquiries', ['name' => 'Spam Lead']);
    }

    public function test_cms_requires_permission(): void
    {
        $nobody = User::factory()->create();
        Livewire::actingAs($nobody)->test(WebsiteManager::class)->assertForbidden();
    }

    public function test_admin_can_publish_a_course_via_cms(): void
    {
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');
        $hidden = Course::where('code', 'HID')->first();

        Livewire::actingAs($admin)->test(WebsiteManager::class)
            ->call('toggleCourse', $hidden->id);

        $this->assertTrue($hidden->fresh()->is_published);
    }
}
