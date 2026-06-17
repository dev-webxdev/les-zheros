<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administration | Les Zheros')</title>
    <meta name="description" content="@yield('description', '')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer"></noscript>
    <link rel="stylesheet" href="{{ asset('assets/css/reset.css') }}?v={{ filemtime(public_path('assets/css/reset.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v={{ filemtime(public_path('assets/css/admin.css')) }}">
    @stack('styles')
    @if (session('admin_toast'))
        <meta name="admin-toast-title" content="{{ session('admin_toast.title') }}">
        <meta name="admin-toast-text" content="{{ session('admin_toast.text') }}">
        <meta name="admin-toast-type" content="{{ session('admin_toast.type', 'success') }}">
    @endif
    <script src="{{ asset('assets/js/admin.js') }}?v={{ filemtime(public_path('assets/js/admin.js')) }}" defer></script>
    @stack('scripts')
</head>
<body @class(['admin-body', 'admin-no-delete' => auth()->user()?->hasAdminRole(\App\Support\AdminAccess::MODERATOR) && ! auth()->user()?->hasAdminRole(\App\Support\AdminAccess::ADMIN)])>
    @php($adminRolePreview = auth()->user()?->adminRolePreview())
    @if($adminRolePreview)
        <aside class="admin-role-preview-banner" aria-label="Mode test de role">
            <span>
                <i class="fa-solid fa-eye"></i>
                Test en cours : {{ \App\Support\AdminAccess::roles()[$adminRolePreview] ?? $adminRolePreview }}
            </span>
            <form action="{{ route('admin.roles.preview.stop') }}" method="post" data-real-form data-skip-confirm>
                @csrf
                <button type="submit">Quitter le test</button>
            </form>
        </aside>
    @endif
    <div class="admin-app">
        @include('partials.admin-sidebar')
        @yield('admin')
    </div>

    @yield('modals')
    <div class="admin-modal" data-confirm-form-modal hidden>
        <div class="admin-modal__backdrop" data-confirm-form-cancel></div>
        <section class="admin-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-form-title" aria-describedby="confirm-form-text">
            <div class="admin-modal__icon" data-confirm-form-icon><i class="fa-regular fa-trash-can"></i></div>
            <div class="admin-modal__content">
                <h2 id="confirm-form-title" data-confirm-form-title>Confirmer la suppression ?</h2>
                <p id="confirm-form-text" data-confirm-form-text>Cette action demande une confirmation.</p>
            </div>
            <div class="admin-modal__actions">
                <button class="admin-secondary-button" type="button" data-confirm-form-cancel>Annuler</button>
                <button class="admin-danger-button" type="button" data-confirm-form-submit>Confirmer</button>
            </div>
        </section>
    </div>
</body>
</html>
