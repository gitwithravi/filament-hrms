<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class MakeModularFilamentResource extends Command
{
    protected $signature = 'make:modular-filament-resource {name} {--fields=} {--generate} {--simple} {--view} {--soft-deletes} {--model} {--migration} {--factory} {--force}';

    protected $description = 'Create a modular Filament resource using the standard command then extracting form/table logic';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

        public function handle()
    {
        $name = $this->argument('name');
        $fields = $this->option('fields') ?? 'DefaultFields';

        $resourceName = Str::studly($name) . 'Resource';
        $fieldClasses = collect(explode(',', $fields))->map(fn($field) => Str::studly(trim($field)))->toArray();

        $this->info("Creating modular Filament resource: {$resourceName}");

        // Step 1: Create standard Filament resource
        $this->info("Step 1: Creating standard Filament resource...");
        if (!$this->createStandardResource($name)) {
            return 1;
        }

        // Step 2: Extract and modularize the generated resource
        $this->info("Step 2: Modularizing the resource...");
        $this->modularizeResource($resourceName, $fieldClasses);

        $this->info("✅ Modular Filament resource created successfully!");
        $this->info("Created files:");
        $this->line("- app/Filament/Resources/{$resourceName}.php (modified)");
        $this->line("- app/Filament/Resources/{$resourceName}/FormSchema.php");
        $this->line("- app/Filament/Resources/{$resourceName}/TableSchema.php");
        $this->line("- app/Filament/Resources/{$resourceName}/Actions.php");

        foreach ($fieldClasses as $fieldClass) {
            $this->line("- app/Filament/Resources/{$resourceName}/Fields/{$fieldClass}.php");
        }

        $this->newLine();
        $this->info("📝 Next steps:");
        $this->line("1. Update your field classes in app/Filament/Resources/{$resourceName}/Fields/");
        $this->line("2. Customize the table columns in app/Filament/Resources/{$resourceName}/TableSchema.php");
        $this->line("3. Customize actions in app/Filament/Resources/{$resourceName}/Actions.php");
        $this->line("4. Add any additional logic to the main resource file");
    }

                    protected function createStandardResource($name)
    {
        $command = "php artisan make:filament-resource {$name}";

        // Pass through relevant options to the standard command
        if ($this->option('model')) {
            $command .= " --model";
        }
        if ($this->option('migration')) {
            $command .= " --migration";
        }
        if ($this->option('factory')) {
            $command .= " --factory";
        }
        if ($this->option('generate')) {
            $command .= " --generate";
        }
        if ($this->option('simple')) {
            $command .= " --simple";
        }
        if ($this->option('view')) {
            $command .= " --view";
        }
        if ($this->option('soft-deletes')) {
            $command .= " --soft-deletes";
        }
        if ($this->option('force')) {
            $command .= " --force";
        }

        $this->line("Executing: {$command}");

        // Change to the project directory
        $currentDir = getcwd();
        chdir(base_path());

        // Execute the command and capture output
        ob_start();
        $exitCode = 0;
        passthru($command, $exitCode);
        $output = ob_get_clean();

        // Return to original directory
        chdir($currentDir);

        if ($exitCode !== 0) {
            $this->error('Failed to create standard Filament resource');
            return false;
        }

        return true;
    }

    protected function modularizeResource($resourceName, $fieldClasses)
    {
        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");

        if (!$this->files->exists($resourcePath)) {
            $this->error("Resource file not found: {$resourcePath}");
            return;
        }

        // Create directories
        $resourceDir = app_path("Filament/Resources/{$resourceName}");
        $fieldsDir = $resourceDir . '/Fields';
        $this->files->ensureDirectoryExists($fieldsDir);

        // Extract form and table logic from the main resource
        $this->extractFormLogic($resourceName, $fieldClasses);
        $this->extractTableLogic($resourceName);

        // Create Actions.php
        $this->createActionsFile($resourceName);

        // Create field classes
        foreach ($fieldClasses as $fieldClass) {
            $this->createFieldFile($resourceName, $fieldClass);
        }

        // Update the main resource file
        $this->updateMainResource($resourceName);
    }

    protected function extractFormLogic($resourceName, $fieldClasses)
    {
        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");
        $content = $this->files->get($resourcePath);

        // Extract the form method content
        $pattern = '/public static function form\(Form \$form\): Form\s*\{(.*?)\n    \}/s';
        preg_match($pattern, $content, $matches);

        $formContent = $matches[1] ?? "\n        return \$form\n            ->schema([\n                //\n            ]);";

        // Create FormSchema.php
        $formSchemaContent = $this->getFormSchemaStub($resourceName, $fieldClasses, $formContent);
        $this->files->put(app_path("Filament/Resources/{$resourceName}/FormSchema.php"), $formSchemaContent);
    }

    protected function extractTableLogic($resourceName)
    {
        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");
        $content = $this->files->get($resourcePath);

        // Extract the table method content
        $pattern = '/public static function table\(Table \$table\): Table\s*\{(.*?)\n    \}/s';
        preg_match($pattern, $content, $matches);

        $tableContent = $matches[1] ?? "\n        return \$table\n            ->columns([\n                //\n            ])\n            ->filters([\n                //\n            ])\n            ->actions(Actions::getActions())\n            ->bulkActions(Actions::getBulkActions());";

        // Replace inline actions with Actions class calls
        $tableContent = $this->replaceInlineActionsWithActionsCalls($tableContent);

        // Create TableSchema.php
        $tableSchemaContent = $this->getTableSchemaStub($resourceName, $tableContent);
        $this->files->put(app_path("Filament/Resources/{$resourceName}/TableSchema.php"), $tableSchemaContent);
    }

    protected function replaceInlineActionsWithActionsCalls($tableContent)
    {
        // Replace ->actions([...]) with ->actions(Actions::getActions())
        $tableContent = preg_replace(
            '/->actions\(\[\s*(?:[^[\]]*(?:\[[^\]]*\])*[^[\]]*)*\s*\]\)/s',
            '->actions(Actions::getActions())',
            $tableContent
        );

        // Replace ->bulkActions([...]) with ->bulkActions(Actions::getBulkActions())
        $tableContent = preg_replace(
            '/->bulkActions\(\[\s*(?:[^[\]]*(?:\[[^\]]*\])*[^[\]]*)*\s*\]\)/s',
            '->bulkActions(Actions::getBulkActions())',
            $tableContent
        );

        return $tableContent;
    }

    protected function updateMainResource($resourceName)
    {
        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");
        $content = $this->files->get($resourcePath);

        // Add imports for FormSchema, TableSchema, and Actions
        $imports = "use App\Filament\Resources\\{$resourceName}\FormSchema;\nuse App\Filament\Resources\\{$resourceName}\TableSchema;\nuse App\Filament\Resources\\{$resourceName}\Actions;";

        // Find the last use statement and add our imports after it
        $content = preg_replace('/^(use .+;)$/m', "$1", $content);
        $lastUsePos = strrpos($content, 'use ');
        if ($lastUsePos !== false) {
            $endOfLine = strpos($content, "\n", $lastUsePos);
            $content = substr_replace($content, "\n{$imports}", $endOfLine, 0);
        }

        // Replace form method
        $content = preg_replace(
            '/public static function form\(Form \$form\): Form\s*\{.*?\n    \}/s',
            "public static function form(Form \$form): Form\n    {\n        return FormSchema::make(\$form);\n    }",
            $content
        );

        // Replace table method
        $content = preg_replace(
            '/public static function table\(Table \$table\): Table\s*\{.*?\n    \}/s',
            "public static function table(Table \$table): Table\n    {\n        return TableSchema::make(\$table);\n    }",
            $content
        );

        $this->files->put($resourcePath, $content);
    }

    protected function createFieldFile($resourceName, $fieldClass)
    {
        $stub = $this->getFieldStub();
        $content = str_replace(
            ['{{ResourceName}}', '{{FieldClass}}'],
            [$resourceName, $fieldClass],
            $stub
        );

        $this->files->put(app_path("Filament/Resources/{$resourceName}/Fields/{$fieldClass}.php"), $content);
    }

    protected function getFormSchemaStub($resourceName, $fieldClasses, $originalFormContent)
    {
        $imports = collect($fieldClasses)->map(fn($class) => "use App\\Filament\\Resources\\{$resourceName}\\Fields\\{$class};")->implode("\n");
        $fieldCalls = collect($fieldClasses)->map(fn($class) => "            ...{$class}::make(),")->implode("\n");

        return <<<PHP
<?php

namespace App\Filament\Resources\\{$resourceName};

use Filament\Forms\Form;
{$imports}

class FormSchema
{
    public static function make(Form \$form): Form
    {
        return \$form
            ->schema([
{$fieldCalls}
            ]);
    }

    /**
     * Original form content from generated resource:
     * You can use this as reference or replace the schema above
     */
    public static function originalForm(Form \$form): Form
    {{$originalFormContent}
    }
}
PHP;
    }

    protected function getTableSchemaStub($resourceName, $originalTableContent)
    {
        // Check if Actions class is referenced in the table content
        $hasActionsReference = strpos($originalTableContent, 'Actions::') !== false;
        $actionsImport = $hasActionsReference ? "use App\\Filament\\Resources\\{$resourceName}\\Actions;\n" : '';

        return <<<PHP
<?php

namespace App\Filament\Resources\\{$resourceName};

use Filament\Tables\Table;
use Filament\Tables;
{$actionsImport}
class TableSchema
{
    public static function make(Table \$table): Table
    {{$originalTableContent}
    }
}
PHP;
    }

    protected function createActionsFile($resourceName)
    {
        $stub = $this->getActionsStub($resourceName);
        $this->files->put(app_path("Filament/Resources/{$resourceName}/Actions.php"), $stub);
    }

    protected function getActionsStub($resourceName)
    {
        return <<<PHP
<?php

namespace App\Filament\Resources\\{$resourceName};

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;

class Actions
{
    public static function getActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->label('Actions')
            ->icon('heroicon-o-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    public static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make(),
        ];
    }
}
PHP;
    }

    protected function getFieldStub()
    {
        return <<<'PHP'
<?php

namespace App\Filament\Resources\{{ResourceName}}\Fields;

use Filament\Forms\Components\Section;

class {{FieldClass}}
{
    public static function make(): array
    {
        return [
            Section::make('{{FieldClass}}')
                ->schema([
                    // Add your form components here
                ])
                ->columns(2),
        ];
    }
}
PHP;
    }
}
