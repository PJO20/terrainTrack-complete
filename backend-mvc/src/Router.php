        // Routes pour les permissions
        $this->addRoute('GET', '/permissions', 'PermissionController@index');
        $this->addRoute('GET', '/permissions/roles', 'PermissionController@roles');
        $this->addRoute('GET', '/permissions/users', 'PermissionController@users');
        $this->addRoute('GET', '/permissions/profile', 'PermissionController@profile');
        
        // API pour les permissions
        $this->addRoute('POST', '/permissions/assign-role', 'PermissionController@assignRoleToUser');
        $this->addRoute('POST', '/permissions/remove-role', 'PermissionController@removeRoleFromUser');
        $this->addRoute('POST', '/permissions/assign-permission', 'PermissionController@assignPermissionToRole');
        $this->addRoute('POST', '/permissions/remove-permission', 'PermissionController@removePermissionFromRole');
        $this->addRoute('POST', '/permissions/create-role', 'PermissionController@createRole');
        $this->addRoute('POST', '/permissions/delete-role', 'PermissionController@deleteRole'); 