<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use LivewireTranslations\Tests\TestCase;

class CreateTranslationCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    public function test_command_is_registered(): void
    {
        $this->assertTrue($this->app['artisan']->has('create:translation'));
    }

    public function test_command_requires_model_argument(): void
    {
        $this->artisan('create:translation')
            ->expectsOutputToContain('Not enough arguments')
            ->assertExitCode(1);
    }

    public function test_command_validates_model_name_format(): void
    {
        $this->artisan('create:translation', ['model' => 'invalid-model-name!'])
            ->expectsOutputToContain('Invalid model name')
            ->assertExitCode(1);
    }

    public function test_command_handles_non_existent_model(): void
    {
        $this->artisan('create:translation', ['model' => 'NonExistentModel'])
            ->expectsOutputToContain('Model class not found')
            ->assertExitCode(1);
    }

    public function test_command_creates_translation_model_and_migration(): void
    {
        $this->createTestModelMigration();

        $this->artisan('create:translation', ['model' => 'TestModel'])
            ->expectsQuestion('Select translatable fields for TestModel', [0, 1])
            ->expectsOutputToContain('Translation model created')
            ->expectsOutputToContain('Migration created')
            ->assertExitCode(0);

        $this->assertTranslationFilesCreated();
    }

    public function test_command_skips_if_translation_already_exists(): void
    {
        $this->createTestModelMigration();
        $this->createExistingTranslationModel();

        $this->artisan('create:translation', ['model' => 'TestModel'])
            ->expectsOutputToContain('Translation model already exists')
            ->assertExitCode(0);
    }

    public function test_command_detects_translatable_fields_from_migration(): void
    {
        $this->createTestModelMigration();

        $this->artisan('create:translation', ['model' => 'TestModel'])
            ->expectsQuestion('Select translatable fields for TestModel', [0])
            ->assertExitCode(0);

        $translationContent = File::get($this->getTranslationModelPath());
        $this->assertStringContainsString("'name'", $translationContent);
        $this->assertStringContainsString("'description'", $translationContent);
    }

    public function test_command_with_force_flag_overwrites_existing(): void
    {
        $this->createTestModelMigration();
        $this->createExistingTranslationModel();

        $this->artisan('create:translation', ['model' => 'TestModel', '--force' => true])
            ->expectsQuestion('Select translatable fields for TestModel', [0])
            ->expectsOutputToContain('Translation model created')
            ->assertExitCode(0);
    }

    public function test_command_with_all_flag_selects_all_fields(): void
    {
        $this->createTestModelMigration();

        $this->artisan('create:translation', ['model' => 'TestModel', '--all' => true])
            ->expectsOutputToContain('Translation model created')
            ->assertExitCode(0);

        $translationContent = File::get($this->getTranslationModelPath());
        $this->assertStringContainsString("'name'", $translationContent);
        $this->assertStringContainsString("'description'", $translationContent);
    }

    protected function createTestModelMigration(): void
    {
        $migrationPath = database_path('migrations/2023_01_01_000000_create_test_models_table.php');
        $migrationContent = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_models', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description');
            \$table->integer('count');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_models');
    }
};";

        File::ensureDirectoryExists(dirname($migrationPath));
        File::put($migrationPath, $migrationContent);
    }

    protected function createExistingTranslationModel(): void
    {
        $modelPath = $this->getTranslationModelPath();
        $modelContent = "<?php

namespace App\Models\Translations;

use Illuminate\Database\Eloquent\Model;

class TestModelTranslation extends Model
{
    protected \$fillable = ['test_model_id', 'language_code', 'name'];
}";

        File::ensureDirectoryExists(dirname($modelPath));
        File::put($modelPath, $modelContent);
    }

    protected function getTranslationModelPath(): string
    {
        return app_path('Models/Translations/TestModelTranslation.php');
    }

    protected function getTranslationMigrationPath(): string
    {
        $files = File::glob(database_path('migrations/*_create_test_model_translations_table.php'));
        return $files[0] ?? '';
    }

    protected function assertTranslationFilesCreated(): void
    {
        $this->assertFileExists($this->getTranslationModelPath());
        $this->assertNotEmpty($this->getTranslationMigrationPath());
        $this->assertFileExists($this->getTranslationMigrationPath());
    }

    protected function cleanupTestFiles(): void
    {
        $files = [
            $this->getTranslationModelPath(),
            app_path('Models/Translations'),
            database_path('migrations/2023_01_01_000000_create_test_models_table.php'),
        ];

        $migrationFiles = File::glob(database_path('migrations/*_create_test_model_translations_table.php'));

        foreach (array_merge($files, $migrationFiles) as $file) {
            if (File::exists($file)) {
                if (File::isDirectory($file)) {
                    File::deleteDirectory($file);
                } else {
                    File::delete($file);
                }
            }
        }
    }
}