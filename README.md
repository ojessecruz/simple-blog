# Simple Blog

Um blog Laravel pronto para usar — listagem pública estilo Medium, CRUD admin com Livewire, Markdown com escape de HTML e preview de rascunhos em nova aba. Zero opinião sobre autenticação: você pluga via middleware do Laravel (Gate, guard, ou qualquer combo).

## Requisitos

- PHP 8.3+
- Laravel 11 / 12 / 13
- Livewire 3.5+
- Tailwind CSS no app consumidor (o pacote shippa classes Tailwind, não compila CSS próprio)

## Instalação

```bash
composer require ojessecruz/simple-blog
```

Publique e rode as migrations:

```bash
php artisan vendor:publish --tag="simple-blog-migrations"
php artisan migrate
```

Publique a config (opcional, mas recomendado):

```bash
php artisan vendor:publish --tag="simple-blog-config"
```

Publique as views (opcional, só se você quiser customizar):

```bash
php artisan vendor:publish --tag="simple-blog-views"
```

## Quickstart

Em três passos você tem o blog rodando.

### 1. Proteja as rotas admin

O pacote **não** embute lógica de autorização. Você pluga via middleware no `config/blog.php`. Exemplos:

```php
// Por Gate específico
'admin_middleware' => ['web', 'auth', 'can:manage-blog'],

// Por guard separado
'admin_middleware' => ['web', 'auth:admin'],

// Combo de middlewares próprios do app
'admin_middleware' => ['web', 'auth', 'verified', 'super.admin'],
```

Se for via Gate, defina-o normalmente no `AuthServiceProvider`:

```php
Gate::define('manage-blog', fn ($user) => $user->is_admin === true);
```

### 2. Configure o model do autor

Aponte para o User do seu app:

```php
// config/blog.php
'author_model' => App\Models\User::class,
```

E faça o User implementar o contract `Author`:

```php
use Jessecruz\SimpleBlog\Contracts\Author;

class User extends Authenticatable implements Author
{
    public function getBlogAuthorName(): string
    {
        return $this->name;
    }

    public function getBlogAuthorInitials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = array_map(
            fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)),
            array_slice($words, 0, 2),
        );

        return implode('', $initials);
    }
}
```

### 3. Acesse

- Público: `https://seuapp.com/blog`
- Admin: `https://seuapp.com/admin/blog`

Pronto.

## Rotas

O pacote registra:

| Método | URL                              | Nome                  | Descrição                       |
|--------|----------------------------------|-----------------------|---------------------------------|
| GET    | `/blog`                          | `blog.index`          | Listagem pública                |
| GET    | `/blog/categoria/{slug}`         | `blog.category`       | Posts de uma categoria          |
| GET    | `/blog/{slug}`                   | `blog.show`           | Post individual                 |
| GET    | `/admin/blog`                    | `blog.admin.index`    | Lista admin (filtro/busca)      |
| GET    | `/admin/blog/criar`              | `blog.admin.create`   | Form de criação                 |
| GET    | `/admin/blog/{slug}/editar`      | `blog.admin.edit`     | Form de edição                  |
| GET    | `/admin/blog/{slug}/preview`     | `blog.admin.preview`  | Pré-visualiza rascunho/agendado |
| GET    | `/admin/blog/categorias`         | `blog.admin.categories` | CRUD de categorias            |

Os prefixos `/blog` e `/admin/blog` são configuráveis (`route_prefix`, `admin_route_prefix`).

## Configuração

Veja `config/blog.php` (publicado) — cada chave tem comentários explicando o que faz e exemplos. Resumo:

- **`route_prefix`** / **`admin_route_prefix`** — onde montar as rotas
- **`public_middleware`** / **`admin_middleware`** — stack de middleware
- **`author_model`** — model do User
- **`layouts.public`** / **`layouts.admin`** — layouts Blade que envolvem o conteúdo
- **`cta_view`** — view opcional renderizada no fim de cada post (ex: pricing, newsletter)
- **`markdown`** — opções passadas para `Str::markdown()`

## Customizando o layout

Por padrão, o pacote usa layouts próprios neutros. Para usar o layout do seu app:

```php
// config/blog.php
'layouts' => [
    'public' => 'layouts.app',
    'admin' => 'layouts.admin',
],
```

Os layouts customizados precisam ter `@yield('content')` no lugar do conteúdo principal.

## Injetando um CTA nos posts

Crie uma view (ex: `resources/views/components/blog-cta.blade.php`) e aponte:

```php
'cta_view' => 'components.blog-cta',
```

A view recebe a variável `$post` e é renderizada após o conteúdo do post (na show) e abaixo do feed (na index).

## Models

O pacote expõe:

- `Jessecruz\SimpleBlog\Models\Post`
- `Jessecruz\SimpleBlog\Models\PostCategory`

Use diretamente se precisar (ex: para gerar sitemap, exportar conteúdo):

```php
use Jessecruz\SimpleBlog\Models\Post;

$posts = Post::published()->with('category')->latest('published_at')->get();
```

## Testando

```bash
composer test
```

## Changelog

Veja [CHANGELOG](CHANGELOG.md).

## License

MIT — veja [License File](LICENSE.md).
