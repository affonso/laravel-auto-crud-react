<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Traits\TableColumnsTrait;

use function Laravel\Prompts\info;

class InertiaReactPageBuilder
{
    use TableColumnsTrait;

    public function __construct()
    {
        $this->modelService = new \Mrmarchone\LaravelAutoCrud\Services\ModelService;
        $this->tableColumnsService = new \Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;
    }

    public function create(array $modelData, bool $overwrite = false): void
    {
        $columns = $this->getAvailableColumns($modelData);
        $modelName = $modelData['modelName'];
        $modelPlural = Str::plural($modelName);
        $modelVariable = lcfirst($modelName);
        $modelKebab = Str::kebab($modelName);
        $routeName = HelperService::toSnakeCase(Str::plural($modelName));

        $pagesPath = base_path(config('laravel_auto_crud.inertia-react.pages_path', 'resources/js/pages'));
        $pageDir = "{$pagesPath}/{$modelPlural}";
        $componentsDir = "{$pageDir}/components";

        if (!is_dir($componentsDir)) {
            mkdir($componentsDir, 0755, true);
        }

        // Generate pages
        $pages = [
            'index' => $this->generateIndexPage($modelName, $modelPlural, $modelVariable, $modelKebab, $routeName),
            'show' => $this->generateShowPage($modelName, $modelVariable, $modelKebab, $routeName, $columns),
        ];

        foreach ($pages as $pageName => $content) {
            $filePath = "{$pageDir}/{$pageName}.tsx";
            if (!file_exists($filePath) || $overwrite) {
                file_put_contents($filePath, $content);
                info("Created: {$filePath}");
            }
        }

        // Generate components
        $components = [
            'columns' => $this->generateColumnsComponent($modelName, $modelVariable, $modelKebab, $routeName, $columns),
            'create-dialog' => $this->generateCreateDialogComponent($modelName, $modelVariable, $modelKebab, $routeName, $columns),
            'edit-dialog' => $this->generateEditDialogComponent($modelName, $modelVariable, $modelKebab, $routeName, $columns),
        ];

        foreach ($components as $componentName => $content) {
            $filePath = "{$componentsDir}/{$componentName}.tsx";
            if (!file_exists($filePath) || $overwrite) {
                file_put_contents($filePath, $content);
                info("Created: {$filePath}");
            }
        }
    }

    private function generateIndexPage(string $modelName, string $modelPlural, string $modelVariable, string $modelKebab, string $routeName): string
    {
        $stubPath = __DIR__ . '/../Stubs/inertia-react/index.tsx.stub';
        $stub = file_get_contents($stubPath);

        return str_replace(
            [
                '{{ model }}',
                '{{ modelPlural }}',
                '{{ modelVariable }}',
                '{{ modelKebab }}',
                '{{ routeName }}',
                '{{ modelPlural | lower }}',
            ],
            [
                $modelName,
                lcfirst($modelPlural),
                $modelVariable,
                $modelKebab,
                $routeName,
                strtolower($modelPlural),
            ],
            $stub
        );
    }

    private function generateShowPage(string $modelName, string $modelVariable, string $modelKebab, string $routeName, array $columns): string
    {
        $stubPath = __DIR__ . '/../Stubs/inertia-react/show.tsx.stub';
        $stub = file_get_contents($stubPath);

        $fieldList = $this->generateFieldList($modelVariable, $columns);

        return str_replace(
            [
                '{{ model }}',
                '{{ modelVariable }}',
                '{{ modelKebab }}',
                '{{ routeName }}',
                '{{ fieldList }}',
                '{{ model | lower }}',
            ],
            [
                $modelName,
                $modelVariable,
                $modelKebab,
                $routeName,
                $fieldList,
                strtolower($modelName),
            ],
            $stub
        );
    }

    private function generateColumnsComponent(string $modelName, string $modelVariable, string $modelKebab, string $routeName, array $columns): string
    {
        $stubPath = __DIR__ . '/../Stubs/inertia-react/components/columns.tsx.stub';
        $stub = file_get_contents($stubPath);

        $columnDefinitions = $this->generateColumnDefinitions($modelVariable, $columns);

        return str_replace(
            [
                '{{ model }}',
                '{{ modelVariable }}',
                '{{ modelKebab }}',
                '{{ routeName }}',
                '{{ columnDefinitions }}',
                '{{ model | lower }}',
            ],
            [
                $modelName,
                $modelVariable,
                $modelKebab,
                $routeName,
                $columnDefinitions,
                strtolower($modelName),
            ],
            $stub
        );
    }

    private function generateCreateDialogComponent(string $modelName, string $modelVariable, string $modelKebab, string $routeName, array $columns): string
    {
        $stubPath = __DIR__ . '/../Stubs/inertia-react/components/create-dialog.tsx.stub';
        $stub = file_get_contents($stubPath);

        $formFields = $this->generateFormFields($columns);
        $formInputs = $this->generateFormInputs($columns, false);

        return str_replace(
            [
                '{{ model }}',
                '{{ modelVariable }}',
                '{{ modelKebab }}',
                '{{ routeName }}',
                '{{ formFields }}',
                '{{ formInputs }}',
                '{{ model | lower }}',
            ],
            [
                $modelName,
                $modelVariable,
                $modelKebab,
                $routeName,
                $formFields,
                $formInputs,
                strtolower($modelName),
            ],
            $stub
        );
    }

    private function generateEditDialogComponent(string $modelName, string $modelVariable, string $modelKebab, string $routeName, array $columns): string
    {
        $stubPath = __DIR__ . '/../Stubs/inertia-react/components/edit-dialog.tsx.stub';
        $stub = file_get_contents($stubPath);

        $formFields = $this->generateFormFields($columns);
        $formFieldsFromModel = $this->generateFormFieldsFromModel($modelVariable, $columns);
        $formInputs = $this->generateFormInputs($columns, false);

        return str_replace(
            [
                '{{ model }}',
                '{{ modelVariable }}',
                '{{ modelKebab }}',
                '{{ routeName }}',
                '{{ formFields }}',
                '{{ formFieldsFromModel }}',
                '{{ formInputs }}',
                '{{ model | lower }}',
            ],
            [
                $modelName,
                $modelVariable,
                $modelKebab,
                $routeName,
                $formFields,
                $formFieldsFromModel,
                $formInputs,
                strtolower($modelName),
            ],
            $stub
        );
    }

    private function generateFieldList(string $modelVariable, array $columns): string
    {
        $fields = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            $label = ucfirst(str_replace('_', ' ', $name));
            $fields[] = <<<HTML
                        <div>
                            <p className="text-sm font-medium text-muted-foreground">{$label}</p>
                            <p className="text-lg">{{ '{' }}{$modelVariable}.{$name} ?? 'N/A'{{ '}' }}</p>
                        </div>
HTML;
        }
        return implode("\n", $fields);
    }

    private function generateColumnDefinitions(string $modelVariable, array $columns): string
    {
        $definitions = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            $label = ucfirst(str_replace('_', ' ', $name));

            $definitions[] = <<<TS
    {
        accessorKey: '{$name}',
        header: '{$label}',
    },
TS;
        }
        return implode("\n", $definitions);
    }

    private function generateFormFields(array $columns): string
    {
        $fields = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            $defaultValue = $this->getDefaultValueForType($column['type']);
            $fields[] = "        {$name}: {$defaultValue},";
        }
        return implode("\n", $fields);
    }

    private function generateFormFieldsFromModel(string $modelVariable, array $columns): string
    {
        $fields = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            $fields[] = "                {$name}: {$modelVariable}.{$name},";
        }
        return implode("\n", $fields);
    }

    private function generateFormInputs(array $columns, bool $includeId = false): string
    {
        $inputs = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            $label = ucfirst(str_replace('_', ' ', $name));
            $type = $column['type'];
            $isTextarea = in_array($type, ['text', 'longtext', 'mediumtext']);

            if ($isTextarea) {
                $inputs[] = <<<TSX
                        <div className="space-y-2">
                            <Label htmlFor="{$name}">{$label}</Label>
                            <Textarea
                                id="{$name}"
                                value={data.{$name}}
                                onChange={(e) => setData('{$name}', e.target.value)}
                                placeholder="Enter {$label}"
                            />
                            {errors.{$name} && (
                                <p className="text-sm text-destructive">{errors.{$name}}</p>
                            )}
                        </div>
TSX;
            } else {
                $inputType = $this->getInputType($type);
                $inputs[] = <<<TSX
                        <div className="space-y-2">
                            <Label htmlFor="{$name}">{$label}</Label>
                            <Input
                                id="{$name}"
                                type="{$inputType}"
                                value={data.{$name}}
                                onChange={(e) => setData('{$name}', e.target.value)}
                                placeholder="Enter {$label}"
                            />
                            {errors.{$name} && (
                                <p className="text-sm text-destructive">{errors.{$name}}</p>
                            )}
                        </div>
TSX;
            }
        }
        return implode("\n", $inputs);
    }

    private function getDefaultValueForType(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'float', 'double' => '0',
            'boolean' => 'false',
            default => "''",
        };
    }

    private function getInputType(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint', 'mediumint' => 'number',
            'decimal', 'float', 'double' => 'number',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime-local',
            'time' => 'time',
            'email' => 'email',
            default => 'text',
        };
    }
}
