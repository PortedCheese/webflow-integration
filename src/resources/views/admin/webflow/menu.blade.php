<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle{{ strstr($currentRoute, 'admin.webflow') !== FALSE ? ' active' : '' }}"
       href="#"
       id="user-dropdown"
       role="button"
       data-toggle="dropdown"
       aria-haspopup="true"
       aria-expanded="false">
        Webflow
    </a>
    <div class="dropdown-menu" aria-labelledby="user-dropdown">
        <a href="{{ route('admin.webflow.list') }}"
           class="dropdown-item">
            Список
        </a>
        <a href="{{ route('admin.webflow.index') }}"
           class="dropdown-item">
            Загрузить
        </a>
    </div>
</li>