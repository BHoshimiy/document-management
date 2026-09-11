<aside class="sidebar">
    <div>
        <div class="brand">Certificate Center</div>
    </div>

    <div class="nav-divider"></div>

    <div class="nav-group">
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <x-icon name="home" />
            {{ __('app.template_library') }}
        </a>
        <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <x-icon name="profile" />
            {{ __('app.profile') }}
        </a>
    </div>

    <div class="nav-divider"></div>

    <div class="nav-group">
        @foreach ($sidebarMenus as $menu)
            <a href="{{ route('menus.show', $menu) }}"
               class="standard-item {{ request()->routeIs('menus.show') && request()->route('menu')?->id === $menu->id ? 'active' : '' }}">
                <span class="standard-dot"></span> {{ $menu->name }}
            </a>
        @endforeach
    </div>

    @can('create', \App\Models\Category::class)
        <div class="nav-divider"></div>

        <div class="nav-group">
            <details class="nav-admin" @if (request()->routeIs('admin.*')) open @endif>
                <summary class="nav-item {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                    <x-icon name="admin" />
                    {{ __('app.admin') }}
                    <x-icon name="chevron" />
                </summary>
                <div class="submenu">
                    <a href="{{ route('admin.menus.index') }}"
                       class="submenu-item {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">
                        {{ __('app.menu') }}
                    </a>
                    <a href="{{ route('admin.categories.index') }}"
                       class="submenu-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        {{ __('app.category') }}
                    </a>
                    <a href="{{ route('admin.folders.index') }}"
                       class="submenu-item {{ request()->routeIs('admin.folders.*') ? 'active' : '' }}">
                        {{ __('app.document_folder') }}
                    </a>
                    <a href="{{ route('admin.companies.index') }}"
                       class="submenu-item {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}">
                        {{ __('app.companies') }}
                    </a>
                </div>
            </details>
        </div>
    @endcan

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-link">
                {{ __('app.sign_out') }}
            </button>
        </form>
    </div>
</aside>
