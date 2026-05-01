<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prefixo das rotas públicas
    |--------------------------------------------------------------------------
    |
    | Define o segmento da URL onde o blog público será montado. Com 'blog',
    | as rotas ficam em /blog, /blog/{slug}, /blog/categoria/{slug}.
    | Trocar por 'artigos' move tudo para /artigos automaticamente.
    |
    */
    'route_prefix' => 'blog',

    /*
    |--------------------------------------------------------------------------
    | Prefixo das rotas administrativas
    |--------------------------------------------------------------------------
    |
    | Onde fica o CRUD de posts e categorias. Padrão: /admin/blog.
    | Pode ser qualquer caminho — só não conflite com rotas existentes
    | do app consumidor.
    |
    */
    'admin_route_prefix' => 'admin/blog',

    /*
    |--------------------------------------------------------------------------
    | Middleware das rotas públicas
    |--------------------------------------------------------------------------
    |
    | Stack aplicada nas rotas /index, /show e /category. Normalmente só
    | 'web' já basta (sessão + CSRF). Adicione middleware extra aqui se
    | quiser, por exemplo, gating por feature flag ou cache.
    |
    */
    'public_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Middleware das rotas administrativas
    |--------------------------------------------------------------------------
    |
    | É AQUI que você protege o admin do blog. O pacote NÃO embute nenhuma
    | lógica de autorização — você pluga via middleware do Laravel.
    |
    | Exemplos:
    |
    |   Gate específico:
    |     ['web', 'auth', 'can:manage-blog']
    |
    |   Guard separado:
    |     ['web', 'auth:admin']
    |
    |   Combo de middlewares próprios do app:
    |     ['web', 'auth', 'verified', 'super.admin']
    |
    | Se a stack autoriza, o app pode ler/criar/editar posts. Se bloqueia,
    | a request é rejeitada antes de chegar no Livewire.
    |
    */
    'admin_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Model do autor
    |--------------------------------------------------------------------------
    |
    | Class do model usado no relacionamento Post::author(). Geralmente é o
    | User do app. O model precisa implementar
    | Jessecruz\SimpleBlog\Contracts\Author para que o pacote consiga
    | renderizar nome e iniciais no layout.
    |
    | Exemplo:
    |     App\Models\User::class
    |
    */
    'author_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    |
    | Views Blade que envolvem o conteúdo do pacote. O pacote shippa um
    | layout padrão limpo (blog::layouts.public e blog::layouts.admin).
    |
    | Para usar o layout do seu app (header/footer próprios, navegação,
    | etc.), aponte aqui:
    |
    |   'public' => 'layouts.app',
    |   'admin'  => 'layouts.admin',
    |
    | Os layouts customizados precisam ter um {{ $slot }} no lugar do
    | conteúdo principal.
    |
    */
    'layouts' => [
        'public' => 'blog::layouts.public',
        'admin' => 'blog::layouts.admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | View do CTA (Call to Action)
    |--------------------------------------------------------------------------
    |
    | View opcional renderizada no fim de cada post (após o conteúdo, antes
    | de "Continue lendo"). Use para injetar pricing, newsletter signup,
    | banner de produto, etc.
    |
    | Deixe `null` para não renderizar nada. A view recebe a variável $post.
    |
    | Exemplo:
    |     'cta_view' => 'components.blog-pricing-cta'
    |
    */
    'cta_view' => null,

    /*
    |--------------------------------------------------------------------------
    | Opções de renderização do Markdown
    |--------------------------------------------------------------------------
    |
    | Passadas direto para Str::markdown() (CommonMark). Por padrão:
    |
    |   - html_input: 'escape' — HTML cru no markdown vira texto literal
    |     (proteção contra XSS quando autores não-confiáveis escrevem)
    |   - allow_unsafe_links: false — bloqueia javascript:, data:, etc.
    |
    | Se TODOS os autores são internos e confiáveis e você quer permitir
    | HTML inline, troque 'html_input' para 'allow'.
    |
    */
    'markdown' => [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
    ],
];
