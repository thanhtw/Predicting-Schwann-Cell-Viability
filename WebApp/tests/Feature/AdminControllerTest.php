<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\MLModel;
use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['RoleCode' => 'admin', 'RoleName' => 'Administrator']);
        Role::create(['RoleCode' => 'user', 'RoleName' => 'User']);
        
        // Create users
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->user = User::factory()->create(['role_id' => 2]);
    }

    /** @test */
    public function admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas(['totalUsers', 'totalModels', 'activeModels', 'adminPredictions']);
    }

    /** @test */
    public function non_admin_cannot_access_admin_dashboard()
    {
        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_dashboard()
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // User Management Tests
    /** @test */
    public function admin_can_view_users_list()
    {
        User::factory()->count(3)->create(['role_id' => 2]);

        $response = $this->actingAs($this->admin)->get(route('admin.users'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertViewHas('users');
    }

    /** @test */
    public function admin_can_view_create_user_form()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        $userData = [
            'FullName' => 'Test User',
            'Gender' => 'Male',
            'BirthDate' => '1990-01-01',
            'Address' => 'Test Address',
            'Username' => 'testuser123',
            'Password' => 'password123'
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'FullName' => 'Test User',
            'Username' => 'testuser123',
            'role_id' => 2
        ]);
    }

    /** @test */
    public function admin_cannot_create_user_with_duplicate_username()
    {
        User::factory()->create(['Username' => 'existinguser']);

        $userData = [
            'FullName' => 'Test User',
            'Gender' => 'Male',
            'BirthDate' => '1990-01-01',
            'Address' => 'Test Address',
            'Username' => 'existinguser',
            'Password' => 'password123'
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors(['Username']);
    }

    /** @test */
    public function admin_can_view_edit_user_form()
    {
        $user = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.edit');
        $response->assertViewHas('user', $user);
    }

    /** @test */
    public function admin_cannot_edit_admin_user()
    {
        $anotherAdmin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $anotherAdmin));

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_can_update_user()
    {
        $user = User::factory()->create(['role_id' => 2]);
        
        $updateData = [
            'FullName' => 'Updated Name',
            'Gender' => 'Female',
            'BirthDate' => '1995-05-05',
            'Address' => 'Updated Address',
            'Username' => 'updatedusername'
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', array_merge(['id' => $user->id], $updateData));
    }

    /** @test */
    public function admin_can_reset_user_password()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $originalPassword = $user->Password;

        $response = $this->actingAs($this->admin)->post(route('admin.users.reset-password', $user));

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertNotEquals($originalPassword, $user->Password);
    }

    /** @test */
    public function admin_can_delete_user_without_predictions()
    {
        $user = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.delete', $user));

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function admin_cannot_delete_user_with_predictions()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $model = MLModel::factory()->create();
        Prediction::factory()->create(['user_id' => $user->id, 'ml_model_id' => $model->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.delete', $user));

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function admin_can_force_delete_user_with_predictions()
    {
        $user = User::factory()->create(['role_id' => 2]);
        $model = MLModel::factory()->create();
        Prediction::factory()->create(['user_id' => $user->id, 'ml_model_id' => $model->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.force-delete', $user));

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('predictions', ['user_id' => $user->id]);
    }

    /** @test */
    public function admin_can_anonymize_user()
    {
        $user = User::factory()->create(['role_id' => 2, 'FullName' => 'Original Name']);
        
        $response = $this->actingAs($this->admin)->post(route('admin.users.anonymize', $user));

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertEquals('Anonymous User', $user->FullName);
        $this->assertStringStartsWith('anon_', $user->Username);
    }

    // ML Model Management Tests
    /** @test */
    public function admin_can_view_models_list()
    {
        MLModel::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.models'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.models.index');
        $response->assertViewHas('models');
    }

    /** @test */
    public function admin_can_view_create_model_form()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.models.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.models.create');
    }

    /** @test */
    public function admin_can_create_model_with_valid_file()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('test_model.h5', 100, 'application/octet-stream');
        
        $modelData = [
            'MLMName' => 'Test Model',
            'model_file' => $file,
            'LibType' => 'keras',
            'IsActive' => '1'
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.models.store'), $modelData);

        $response->assertRedirect(route('admin.models'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ml_models', [
            'MLMName' => 'Test Model',
            'LibType' => 'keras',
            'IsActive' => true
        ]);
    }

    /** @test */
    public function admin_cannot_create_model_without_file()
    {
        $modelData = [
            'MLMName' => 'Test Model',
            'LibType' => 'keras'
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.models.store'), $modelData);

        $response->assertSessionHasErrors(['model_file']);
    }

    /** @test */
    public function admin_can_view_edit_model_form()
    {
        $model = MLModel::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.models.edit', $model));

        $response->assertStatus(200);
        $response->assertViewIs('admin.models.edit');
        $response->assertViewHas('model', $model);
    }

    /** @test */
    public function admin_can_update_model()
    {
        $model = MLModel::factory()->create();
        
        $updateData = [
            'MLMName' => 'Updated Model Name',
            'LibType' => 'pytorch',
            'IsActive' => '1'
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.models.update', $model), $updateData);

        $response->assertRedirect(route('admin.models'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ml_models', [
            'id' => $model->id,
            'MLMName' => 'Updated Model Name',
            'LibType' => 'pytorch'
        ]);
    }

    /** @test */
    public function admin_can_delete_model_without_predictions()
    {
        $model = MLModel::factory()->create(['MLMName' => 'Test Model']);

        $response = $this->actingAs($this->admin)->delete(route('admin.models.delete', $model));

        $response->assertRedirect(route('admin.models'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('ml_models', ['id' => $model->id]);
    }

    /** @test */
    public function admin_cannot_delete_model_with_predictions()
    {
        $model = MLModel::factory()->create();
        Prediction::factory()->create(['ml_model_id' => $model->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.models.delete', $model));

        $response->assertRedirect(route('admin.models'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ml_models', ['id' => $model->id]);
    }

    /** @test */
    public function admin_can_view_prediction_form()
    {
        MLModel::factory()->create(['IsActive' => true]);

        $response = $this->actingAs($this->admin)->get(route('admin.predict'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.predict');
        $response->assertViewHas('models');
    }

    /** @test */
    public function admin_can_make_prediction_with_valid_data()
    {
        Http::fake([
            '*/predict/health' => Http::response(['status' => 'healthy'], 200),
            '*/predict/model' => Http::response(['prediction' => 85.5], 200),
        ]);

        $model = MLModel::factory()->create(['IsActive' => true]);
        
        $predictionData = [
            'pc_mxene_loading' => 1.1,
            'laminin_peptide_loading' => 200,
            'stimulation_frequency' => 5.0,
            'applied_voltage' => 4.0,
            'ml_model_id' => $model->id
        ];

        $response = $this->actingAs($this->admin)->postJson(route('admin.predict.make'), $predictionData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('predictions', [
            'user_id' => $this->admin->id,
            'ml_model_id' => $model->id
        ]);
    }

    /** @test */
    public function admin_can_view_prediction_history()
    {
        $model = MLModel::factory()->create();
        Prediction::factory()->count(3)->create([
            'user_id' => $this->admin->id,
            'ml_model_id' => $model->id
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.history'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.history');
        $response->assertViewHas('predictions');
    }

    /** @test */
    public function admin_prediction_validation_fails_with_invalid_data()
    {
        $invalidData = [
            'pc_mxene_loading' => 'invalid', // not numeric
            'laminin_peptide_loading' => 'invalid', // not numeric
            'stimulation_frequency' => 'invalid', // not numeric
            'applied_voltage' => '', // required
            'ml_model_id' => 999 // non-existent model
        ];

        $response = $this->actingAs($this->admin)->postJson(route('admin.predict.make'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'pc_mxene_loading',
            'laminin_peptide_loading',
            'stimulation_frequency',
            'applied_voltage',
            'ml_model_id'
        ]);
    }

    /** @test */
    public function admin_cannot_make_prediction_with_inactive_model()
    {
        $model = MLModel::factory()->create(['IsActive' => false]);
        
        $predictionData = [
            'pc_mxene_loading' => 0.1,
            'laminin_peptide_loading' => 50,
            'stimulation_frequency' => 1.5,
            'applied_voltage' => 2.0,
            'ml_model_id' => $model->id
        ];

        $response = $this->actingAs($this->admin)->postJson(route('admin.predict.make'), $predictionData);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }
}
