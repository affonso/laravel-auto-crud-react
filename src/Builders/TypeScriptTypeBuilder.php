<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Traits\TableColumnsTrait;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

class TypeScriptTypeBuilder
{
    use TableColumnsTrait;

    public function __construct()
    {
        $this->modelService = new \Mrmarchone\LaravelAutoCrud\Services\ModelService;
        $this->tableColumnsService = new \Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;
    }

    public function create(array $modelData, bool $overwrite = false): string
    {
        $columns = $this->getAvailableColumns($modelData);
        $modelName = $modelData['modelName'];
        $kebabCase = Str::kebab($modelName);

        $typesPath = base_path(config('laravel_auto_crud.inertia-react.types_path', 'resources/js/types'));
        $filePath = "{$typesPath}/{$kebabCase}.d.ts";

        if (!$overwrite && file_exists($filePath)) {
            $overwrite = confirm(
                label: "TypeScript type file already exists, do you want to overwrite it? {$filePath}"
            );
            if (!$overwrite) {
                return $filePath;
            }
        }

        File::ensureDirectoryExists(dirname($filePath), 0777, true);

        $fields = $this->generateFields($columns);
        $content = $this->generateTypeScriptInterface($modelName, $fields);

        File::put($filePath, $content);

        info("Created: {$filePath}");

        return $filePath;
    }

    private function generateFields(array $columns): string
    {
        $fields = [];

        // Add id field (always present)
        $fields[] = '    id: number;';

        // Add columns from database
        foreach ($columns as $column) {
            $tsType = $this->mapTypeToTypeScript($column['type'], $column['is_nullable']);
            $fieldName = $column['name'];
            $fields[] = "    {$fieldName}: {$tsType};";
        }

        // Add timestamps (always present in Laravel models)
        $fields[] = '    created_at: string;';
        $fields[] = '    updated_at: string;';

        return implode("\n", $fields);
    }

    private function mapTypeToTypeScript(string $type, bool $isNullable): string
    {
        $tsType = match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint', 'mediumint' => 'number',
            'decimal', 'float', 'double', 'real' => 'number',
            'boolean' => 'boolean',
            'date', 'datetime', 'timestamp', 'time' => 'string',
            'json', 'jsonb' => 'Record<string, any>',
            'array' => 'any[]',
            default => 'string',
        };

        return $isNullable ? "{$tsType} | null" : $tsType;
    }

    private function generateTypeScriptInterface(string $modelName, string $fields): string
    {
        $stubPath = __DIR__ . '/../Stubs/typescript.type.stub';
        $stub = file_get_contents($stubPath);

        return str_replace(
            ['{{ model }}', '{{ fields }}'],
            [$modelName, $fields],
            $stub
        );
    }
}
