# Laravel Auto CRUD - Inertia React Extension

Fork do [mrmarchone/laravel-auto-crud](https://github.com/mrmarchone/laravel-auto-crud) com suporte adicional para Laravel 12 React Starter Kit (Inertia + React 19 + TypeScript + shadcn/ui).

## Novidades

Esta extensão adiciona a opção `--type=inertia-react` que gera:

- ✅ Controllers com `Inertia::render()`
- ✅ Páginas React/TSX em `resources/js/pages/`
- ✅ Types TypeScript em `resources/js/types/`
- ✅ Integração com shadcn/ui (DataTable, Dialog, Form)
- ✅ Suporte a Repository Pattern
- ✅ Suporte a Spatie Data

## Requisitos

- Laravel 12+
- PHP 8.2+
- Inertia.js
- React 19+
- TypeScript
- shadcn/ui components instalados:
  - `button`
  - `card`
  - `dialog`
  - `dropdown-menu`
  - `input`
  - `label`
  - `textarea`
  - `data-table` (custom component)

## Instalação

### Passo 1: Criar um novo projeto Laravel 12 com React Starter Kit

Primeiro, crie um novo projeto Laravel 12 usando o React Starter Kit (que já inclui Inertia.js, React 19 e TypeScript):

```bash
composer create-project laravel/laravel meu-projeto
cd meu-projeto
```

Instale o React Starter Kit:

```bash
php artisan breeze:install react --typescript
```

Instale as dependências do frontend:

```bash
npm install
```

### Passo 2: Instalar componentes shadcn/ui

Inicialize o shadcn/ui no projeto:

```bash
npx shadcn@latest init
```

Instale os componentes necessários:

```bash
npx shadcn@latest add button card dialog dropdown-menu input label textarea table
```

### Passo 3: Instalar o pacote Laravel Auto CRUD (branch inertia-react)

Adicione o repositório no seu `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/danielfcastro/laravel-auto-crud-react"
        }
    ]
}
```

Instale o pacote especificando o branch `inertia-react`:

```bash
composer require mrmarchone/laravel-auto-crud:dev-inertia-react --dev
```

### Passo 4: Publicar a configuração

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --provider="Mrmarchone\LaravelAutoCrud\LaravelAutoCrudServiceProvider" --tag="auto-crud-config"
```

### Passo 5: Criar o componente DataTable (opcional mas recomendado)

Crie o componente `data-table.tsx` em `resources/js/components/ui/data-table.tsx`. Este é um componente customizado baseado no `@tanstack/react-table`:

```bash
npm install @tanstack/react-table
```

Veja a seção [DataTable Component](#datatable-component) abaixo para um exemplo de implementação.

## Uso Básico

### Gerar CRUD Inertia React

```bash
php artisan auto-crud:generate --model=Post --type=inertia-react
```

Isso irá gerar:

```
app/Http/Controllers/PostController.php
resources/js/pages/Posts/
├── index.tsx                      # Listagem com DataTable
├── show.tsx                       # Visualização
└── components/
    ├── columns.tsx                # Definição colunas DataTable
    ├── create-dialog.tsx          # Modal de criação
    └── edit-dialog.tsx            # Modal de edição
resources/js/types/post.d.ts       # Interface TypeScript
routes/web.php                     # Rota adicionada
```

### Com Repository Pattern

```bash
php artisan auto-crud:generate --model=Post --type=inertia-react --repository
```

Adicionalmente gera:
- `app/Repositories/PostRepository.php`
- `app/Services/PostService.php`

### Com Spatie Data

```bash
php artisan auto-crud:generate --model=Post --type=inertia-react --pattern=spatie-data
```

Usa `Spatie\LaravelData\Data` em vez de FormRequest.

### Exemplo Completo

```bash
php artisan auto-crud:generate \
  --model=Post \
  --type=inertia-react \
  --repository \
  --overwrite
```

## Estrutura dos Arquivos Gerados

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

## Configuração

Edite `config/laravel_auto_crud.php`:

```php
'inertia-react' => [
    // Path para páginas React/TSX
    'pages_path' => 'resources/js/pages',

    // Path para tipos TypeScript
    'types_path' => 'resources/js/types',

    // Usar dialogs em vez de páginas create/edit separadas
    'use_dialogs' => true,
],
```

## Mapeamento de Tipos SQL → TypeScript

| SQL Type | TypeScript Type |
|----------|----------------|
| integer, bigint, smallint | `number` |
| decimal, float, double | `number` |
| boolean | `boolean` |
| date, datetime, timestamp | `string` |
| json, jsonb | `Record<string, any>` |
| array | `any[]` |
| text, varchar, char | `string` |

Campos nullable: adiciona `| null` ao tipo.

## Convenções

### Nomenclatura
- Model: `Post`
- Controller: `PostController`
- Páginas: `resources/js/pages/Posts/`
- Type: `resources/js/types/post.d.ts`
- Rota: `posts.index`, `posts.store`, etc.

### Componentes shadcn/ui Utilizados
- `Button` - Botões de ação
- `Card` - Exibição de dados
- `Dialog` - Modais de criação/edição
- `DropdownMenu` - Menu de ações
- `Input` / `Textarea` - Campos de formulário
- `Label` - Labels de formulário
- `DataTable` - Tabela com paginação

### Estrutura de Pastas
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

## Compatibilidade

Esta extensão é compatível com todas as features do pacote original:

- ✅ API Controllers
- ✅ Web Controllers (Blade)
- ✅ **Inertia React Controllers (NOVO)**
- ✅ Repository Pattern
- ✅ Spatie Data Pattern
- ✅ Form Requests
- ✅ API Resources
- ✅ Enum Generation

Você pode combinar tipos:

```bash
# Gerar API + Inertia React juntos
php artisan auto-crud:generate --model=Post --type=api --type=inertia-react
```

## Diferenças do Pacote Original

### O que foi adicionado:
1. Novo tipo: `--type=inertia-react`
2. Geração de páginas React/TSX
3. Geração de tipos TypeScript
4. Integração com shadcn/ui
5. Dialogs para criação/edição
6. DataTable component

### O que permanece igual:
- Toda funcionalidade original (API, Web, Repository, Spatie Data)
- Estrutura de comandos
- Configuração base

## DataTable Component

O componente DataTable é customizado e não vem por padrão com o shadcn/ui. Crie o arquivo `resources/js/components/ui/data-table.tsx`:

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
                                    Nenhum resultado encontrado.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            {pagination && pagination.lastPage > 1 && (
                <div className="flex items-center justify-between py-4">
                    <span className="text-sm text-muted-foreground">
                        Página {pagination.currentPage} de {pagination.lastPage} ({pagination.total} itens)
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handlePageChange(pagination.currentPage - 1)}
                            disabled={pagination.currentPage <= 1}
                        >
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handlePageChange(pagination.currentPage + 1)}
                            disabled={pagination.currentPage >= pagination.lastPage}
                        >
                            Próximo
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
```

## Troubleshooting

### Erro: "Class 'Inertia' not found"
O Laravel 12 React Starter Kit já inclui o Inertia.js. Se estiver usando uma instalação manual, instale:
```bash
composer require inertiajs/inertia-laravel
npm install @inertiajs/react
```

### Erro: Componentes shadcn/ui não encontrados
Instale os componentes necessários:
```bash
npx shadcn@latest add button card dialog dropdown-menu input label textarea table
```

### DataTable não encontrado
Veja a seção [DataTable Component](#datatable-component) acima para criar o componente customizado.

### Erro: Module '@tanstack/react-table' not found
Instale a dependência:
```bash
npm install @tanstack/react-table
```

## Resumo da Instalação

```bash
# 1. Criar projeto Laravel 12
composer create-project laravel/laravel meu-projeto
cd meu-projeto

# 2. Instalar React Starter Kit
php artisan breeze:install react --typescript
npm install

# 3. Inicializar e instalar componentes shadcn/ui
npx shadcn@latest init
npx shadcn@latest add button card dialog dropdown-menu input label textarea table
npm install @tanstack/react-table

# 4. Adicionar repositório no composer.json e instalar o pacote
composer require mrmarchone/laravel-auto-crud:dev-inertia-react --dev

# 5. Publicar configuração
php artisan vendor:publish --provider="Mrmarchone\LaravelAutoCrud\LaravelAutoCrudServiceProvider" --tag="auto-crud-config"

# 6. Criar o componente DataTable (veja a seção acima)

# 7. Gerar CRUD
php artisan auto-crud:generate --model=SeuModel --type=inertia-react
```

## Licença

MIT License - igual ao pacote original.

## Créditos

- Pacote original: [mrmarchone/laravel-auto-crud](https://github.com/mrmarchone/laravel-auto-crud)
- Extensão Inertia React: Desenvolvida como fork
