# Laravel Auto CRUD - Inertia React Extension

Fork of [mrmarchone/laravel-auto-crud](https://github.com/mrmarchone/laravel-auto-crud) with additional support for Laravel 12 React Starter Kit (Inertia + React 19 + TypeScript + shadcn/ui).

## What's New

This extension adds the `--type=inertia-react` option that generates:

- ✅ Controllers with `Inertia::render()`
- ✅ React/TSX pages in `resources/js/pages/`
- ✅ TypeScript types in `resources/js/types/`
- ✅ Integration with shadcn/ui (DataTable, Dialog, Form)
- ✅ Repository Pattern support
- ✅ Spatie Data support

## Requirements

- Laravel 12+
- PHP 8.2+
- Inertia.js
- React 19+
- TypeScript
- shadcn/ui components installed:
  - `button`
  - `card`
  - `dialog`
  - `dropdown-menu`
  - `input`
  - `label`
  - `textarea`
  - `data-table` (custom component)

## Installation

### Step 1: Create a new Laravel 12 project with React Starter Kit

First, make sure you have the Laravel Installer installed:

```bash
composer global require laravel/installer
```

Create a new Laravel 12 project with the React Starter Kit:

```bash
laravel new my-project --react
```

The installer will automatically configure:
- Inertia 2
- React 19
- TypeScript
- Tailwind CSS
- shadcn/ui (components already included)

Install the dependencies and compile the assets:

```bash
cd my-project
npm install && npm run build
```

### Step 2: Install additional shadcn/ui components (if needed)

The React Starter Kit already includes several shadcn/ui components. If you need additional components:

```bash
npx shadcn@latest add button card dialog dropdown-menu input label textarea table
```

### Step 3: Install the Laravel Auto CRUD package

Install the package:

```bash
composer require affonso/laravel-auto-crud-react:dev-main --dev
```

### Step 4: Publish the configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="auto-crud-config"
```

### Step 5: Create the DataTable component (optional but recommended)

Create the `data-table.tsx` component in `resources/js/components/ui/data-table.tsx`. This is a custom component based on `@tanstack/react-table`:

```bash
npm install @tanstack/react-table
```

See the [DataTable Component](#datatable-component) section below for an implementation example.

## Basic Usage

> **Important:** The Model must exist before running the command. The package reads information from the existing Model (columns, data types) to generate the CRUD files.

### Prerequisite: Create the Model

Before generating the CRUD, create the Model and migration:

```bash
php artisan make:model Post -m
```

Define the columns in the migration and run:

```bash
php artisan migrate
```

### Generate Inertia React CRUD

```bash
php artisan auto-crud:generate --model=Post --type=inertia-react
```

This will generate:

```
app/Http/Controllers/PostController.php
resources/js/pages/Posts/
├── index.tsx                      # List with DataTable
├── show.tsx                       # View
└── components/
    ├── columns.tsx                # DataTable columns definition
    ├── create-dialog.tsx          # Creation modal
    └── edit-dialog.tsx            # Edit modal
resources/js/types/post.d.ts       # TypeScript interface
routes/web.php                     # Route added
```

### With Repository Pattern

```bash
php artisan auto-crud:generate --model=Post --type=inertia-react --repository
```

Additionally generates:
- `app/Repositories/PostRepository.php`
- `app/Services/PostService.php`

### With Spatie Data

```bash
php artisan auto-crud:generate --model=Post --type=inertia-react --pattern=spatie-data
```

Uses `Spatie\LaravelData\Data` instead of FormRequest.

### Complete Example

```bash
php artisan auto-crud:generate \
  --model=Post \
  --type=inertia-react \
  --repository \
  --overwrite
```

## Generated Files Structure

### Controller (PostController.php)

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;

class PostController extends Controller
{
    public function index(): InertiaResponse
    {
        $posts = Post::latest()->paginate(10);

        return Inertia::render('Posts/index', [
            'posts' => $posts,
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        Post::create($request->validated());

        return redirect()->route('posts.index')
            ->with('success', 'Post created successfully');
    }

    public function show(Post $post): InertiaResponse
    {
        return Inertia::render('Posts/show', [
            'post' => $post,
        ]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        return redirect()->route('posts.index')
            ->with('success', 'Post updated successfully');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('posts.index')
            ->with('success', 'Post deleted successfully');
    }
}
```

### TypeScript Interface (post.d.ts)

```typescript
export interface Post {
    id: number;
    title: string;
    content: string | null;
    published: boolean;
    created_at: string;
    updated_at: string;
}
```

### Index Page (index.tsx)

```tsx
import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/ui/data-table';
import { columns } from './components/columns';
import { CreateDialog } from './components/create-dialog';
import { EditDialog } from './components/edit-dialog';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import type { Post } from '@/types/post';

interface PostsPageProps {
    posts: {
        data: Post[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function PostsPage({ posts }: PostsPageProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [selectedPost, setSelectedPost] = useState<Post | null>(null);

    const handleEdit = (post: Post) => {
        setSelectedPost(post);
        setEditOpen(true);
    };

    return (
        <AppLayout>
            <Head title="Posts" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-3xl font-bold">Posts</h1>
                    <Button onClick={() => setCreateOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        New Post
                    </Button>
                </div>

                <DataTable
                    columns={columns(handleEdit)}
                    data={posts.data}
                    pagination={{
                        currentPage: posts.current_page,
                        lastPage: posts.last_page,
                        perPage: posts.per_page,
                        total: posts.total,
                    }}
                />

                <CreateDialog open={createOpen} onOpenChange={setCreateOpen} />
                {selectedPost && (
                    <EditDialog
                        open={editOpen}
                        onOpenChange={setEditOpen}
                        post={selectedPost}
                    />
                )}
            </div>
        </AppLayout>
    );
}
```

## Configuration

Edit `config/laravel_auto_crud.php`:

```php
'inertia-react' => [
    // Path for React/TSX pages
    'pages_path' => 'resources/js/pages',

    // Path for TypeScript types
    'types_path' => 'resources/js/types',

    // Use dialogs instead of separate create/edit pages
    'use_dialogs' => true,
],
```

## SQL to TypeScript Type Mapping

| SQL Type | TypeScript Type |
|----------|----------------|
| integer, bigint, smallint | `number` |
| decimal, float, double | `number` |
| boolean | `boolean` |
| date, datetime, timestamp | `string` |
| json, jsonb | `Record<string, any>` |
| array | `any[]` |
| text, varchar, char | `string` |

Nullable fields: adds `| null` to the type.

## Conventions

### Naming
- Model: `Post`
- Controller: `PostController`
- Pages: `resources/js/pages/Posts/`
- Type: `resources/js/types/post.d.ts`
- Route: `posts.index`, `posts.store`, etc.

### shadcn/ui Components Used
- `Button` - Action buttons
- `Card` - Data display
- `Dialog` - Create/edit modals
- `DropdownMenu` - Action menu
- `Input` / `Textarea` - Form fields
- `Label` - Form labels
- `DataTable` - Table with pagination

### Folder Structure
```
resources/js/
├── pages/
│   └── {ModelPlural}/
│       ├── index.tsx
│       ├── show.tsx
│       └── components/
│           ├── columns.tsx
│           ├── create-dialog.tsx
│           └── edit-dialog.tsx
├── types/
│   └── {model-kebab}.d.ts
└── layouts/
    └── app-layout.tsx
```

## Compatibility

This extension is compatible with all features of the original package:

- ✅ API Controllers
- ✅ Web Controllers (Blade)
- ✅ **Inertia React Controllers (NEW)**
- ✅ Repository Pattern
- ✅ Spatie Data Pattern
- ✅ Form Requests
- ✅ API Resources
- ✅ Enum Generation

You can combine types:

```bash
# Generate API + Inertia React together
php artisan auto-crud:generate --model=Post --type=api --type=inertia-react
```

## Differences from Original Package

### What was added:
1. New type: `--type=inertia-react`
2. React/TSX page generation
3. TypeScript type generation
4. shadcn/ui integration
5. Dialogs for create/edit
6. DataTable component

### What remains the same:
- All original functionality (API, Web, Repository, Spatie Data)
- Command structure
- Base configuration

## DataTable Component

The DataTable component is custom and does not come by default with shadcn/ui. Create the file `resources/js/components/ui/data-table.tsx`:

```tsx
import {
    ColumnDef,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
}

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    pagination?: PaginationProps;
    routeName?: string;
}

export function DataTable<TData, TValue>({
    columns,
    data,
    pagination,
    routeName,
}: DataTableProps<TData, TValue>) {
    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
    });

    const handlePageChange = (page: number) => {
        if (routeName) {
            router.get(route(routeName), { page }, { preserveState: true });
        }
    };

    return (
        <div>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef.header,
                                                  header.getContext()
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext()
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center"
                                >
                                    No results found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            {pagination && pagination.lastPage > 1 && (
                <div className="flex items-center justify-between py-4">
                    <span className="text-sm text-muted-foreground">
                        Page {pagination.currentPage} of {pagination.lastPage} ({pagination.total} items)
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handlePageChange(pagination.currentPage - 1)}
                            disabled={pagination.currentPage <= 1}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handlePageChange(pagination.currentPage + 1)}
                            disabled={pagination.currentPage >= pagination.lastPage}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
```

## Troubleshooting

### Error: "Class 'Inertia' not found"
The Laravel 12 React Starter Kit already includes Inertia.js. If you are using a manual installation without the starter kit, install:
```bash
composer require inertiajs/inertia-laravel
npm install @inertiajs/react
```

### Error: shadcn/ui components not found
Install the required components:
```bash
npx shadcn@latest add button card dialog dropdown-menu input label textarea table
```

### DataTable not found
See the [DataTable Component](#datatable-component) section above to create the custom component.

### Error: Module '@tanstack/react-table' not found
Install the dependency:
```bash
npm install @tanstack/react-table
```

## Installation Summary

```bash
# 1. Install Laravel Installer (if needed)
composer global require laravel/installer

# 2. Create Laravel 12 project with React Starter Kit
laravel new my-project --react
cd my-project
npm install && npm run build

# 3. Install additional dependencies (if needed)
npx shadcn@latest add button card dialog dropdown-menu input label textarea table
npm install @tanstack/react-table

# 4. Install the package
composer require affonso/laravel-auto-crud-react --dev

# 5. Publish configuration
php artisan vendor:publish --tag="auto-crud-config"

# 6. Create the DataTable component (see section above)

# 7. Generate CRUD
php artisan auto-crud:generate --model=YourModel --type=inertia-react
```

## License

MIT License.

## Credits

- Based on: [mrmarchone/laravel-auto-crud](https://github.com/mrmarchone/laravel-auto-crud)
- Fork with Inertia React support: [affonso/laravel-auto-crud-react](https://github.com/affonso/laravel-auto-crud-react)
