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

```bash
composer require mrmarchone/laravel-auto-crud --dev
```

Publique a configuração:

```bash
php artisan vendor:publish --provider="Mrmarchone\LaravelAutoCrud\LaravelAutoCrudServiceProvider" --tag="auto-crud-config"
```

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

## Troubleshooting

### Erro: "Class 'Inertia' not found"
Instale o Inertia.js:
```bash
composer require inertiajs/inertia-laravel
npm install @inertiajs/react
```

### Erro: Componentes shadcn/ui não encontrados
Instale os componentes necessários:
```bash
npx shadcn-ui@latest add button card dialog dropdown-menu input label textarea
```

### DataTable não encontrado
Crie um componente customizado `data-table.tsx` ou use uma biblioteca como `@tanstack/react-table`.

## Licença

MIT License - igual ao pacote original.

## Créditos

- Pacote original: [mrmarchone/laravel-auto-crud](https://github.com/mrmarchone/laravel-auto-crud)
- Extensão Inertia React: Desenvolvida como fork
