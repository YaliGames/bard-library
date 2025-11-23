# RBAC 权限管理系统规范文档

## 📋 目录
1. [系统架构](#系统架构)
2. [数据库设计](#数据库设计)
3. [权限定义](#权限定义)
4. [后端实现](#后端实现)
5. [前端实现](#前端实现)
6. [迁移策略](#迁移策略)

---

## 🏗️ 系统架构

### 核心概念
```
用户 (User) ←→ 角色 (Role) ←→ 权限 (Permission)
     N:M              N:M
```

- **用户**: 系统使用者
- **角色**: 权限的集合 (如: 管理员、编辑、普通用户、访客)
- **权限**: 具体的操作权限 (如: `books.create`, `users.manage`)

---

## 🗄️ 数据库设计

### 1. roles 表
```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50)->unique();           // admin, editor, user, guest
    $table->string('display_name', 100);            // 管理员, 编辑, 用户, 访客
    $table->text('description')->nullable();
    $table->boolean('is_system')->default(false);   // 系统角色不可删除
    $table->integer('priority')->default(0);        // 优先级,数字越大权限越高
    $table->timestamps();
});
```

### 2. permissions 表
```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100)->unique();          // books.create
    $table->string('display_name', 100);            // 创建图书
    $table->string('group', 50);                    // books, users, shelves, settings
    $table->text('description')->nullable();
    $table->boolean('is_system')->default(false);   // 系统权限不可删除
    $table->timestamps();
    
    $table->index('group');
});
```

### 3. role_user 表 (多对多)
```php
Schema::create('role_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->timestamp('created_at')->nullable();
    
    $table->unique(['user_id', 'role_id']);
});
```

### 4. permission_role 表 (多对多)
```php
Schema::create('permission_role', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->foreignId('permission_id')->constrained()->onDelete('cascade');
    $table->timestamp('created_at')->nullable();
    
    $table->unique(['role_id', 'permission_id']);
});
```

### 5. 扩展 system_settings 表
```php
Schema::table('system_settings', function (Blueprint $table) {
    $table->foreignId('default_role_id')->nullable()->constrained('roles');
    $table->boolean('require_email_verification')->default(true);
    $table->boolean('allow_self_registration')->default(true);
});
```

---

## 🔐 权限定义

### 权限分组和命名规范

#### 1. 图书管理 (books)
- `books.view` - 查看图书管理（访问后台图书管理列表页面）
- `books.create` - 创建图书
- `books.edit` - 编辑图书信息
- `books.delete` - 删除图书
- `books.download` - 下载图书文件

#### 2. 作者管理 (authors)
- `authors.view` - 查看作者管理（访问后台作者管理列表页面）
- `authors.create` - 创建作者
- `authors.edit` - 编辑作者
- `authors.delete` - 删除作者

#### 3. 标签管理 (tags)
- `tags.view` - 查看标签管理（访问后台标签管理列表页面）
- `tags.create` - 创建标签
- `tags.edit` - 编辑标签
- `tags.delete` - 删除标签

#### 4. 丛书管理 (series)
- `series.view` - 查看丛书管理（访问后台丛书管理列表页面）
- `series.create` - 创建丛书
- `series.edit` - 编辑丛书
- `series.delete` - 删除丛书

#### 5. 书架管理 (shelves)
- `shelves.view` - 查看书架管理（访问后台书架管理列表页面）
- `shelves.create` - 创建书架
- `shelves.edit` - 编辑自己的书架
- `shelves.delete` - 删除自己的书架
- `shelves.create_global` - 创建/设置全局书架
- `shelves.create_public` - 创建/设置公开书架
- `shelves.manage_all` - 管理所有书架

#### 6. 文件管理 (files)
- `files.view` - 查看所有文件列表
- `files.upload` - 上传文件
- `files.delete` - 删除文件
- `files.cleanup` - 清理未使用文件

#### 7. 用户管理 (users)
- `users.view` - 查看用户列表
- `users.create` - 创建用户
- `users.edit` - 编辑用户信息
- `users.delete` - 删除用户
- `users.assign_roles` - 分配角色

#### 8. 角色管理 (roles)
- `roles.view` - 查看角色列表
- `roles.create` - 创建角色
- `roles.edit` - 编辑角色
- `roles.delete` - 删除角色
- `roles.assign_permissions` - 分配权限

#### 9. 系统设置 (settings)
- `settings.view` - 查看系统设置
- `settings.edit` - 修改系统设置

#### 10. 元数据 (metadata)
- `metadata.search` - 搜索元数据（暂不适用）
- `metadata.fetch` - 获取元数据详情（暂不适用）
- `metadata.batch_scrape` - 批量元数据刮削（创建和管理批量元数据刮削任务）

---

## 🎭 预定义角色

> **说明**：角色定义在 `database/seeders/RolesSeeder.php` 中，通过运行 seeder 自动创建。

### 1. 超级管理员 (super_admin)
- **显示名称**：超级管理员
- **优先级**：1000
- **描述**：拥有所有权限的超级管理员
- **权限**：`*`（特殊通配符，表示所有权限）
- **特性**：
  - 系统角色（不可删除）
  - 自动拥有所有现有和未来新增的权限
  - 最高优先级，不受任何限制

### 2. 管理员 (admin)
- **显示名称**：管理员
- **优先级**：900
- **描述**：系统管理员，拥有大部分管理权限
- **权限组**：
  - `books.*` - 所有图书管理权限
  - `authors.*` - 所有作者管理权限
  - `tags.*` - 所有标签管理权限
  - `series.*` - 所有丛书管理权限
  - `shelves.*` - 所有书架管理权限
  - `files.*` - 所有文件管理权限
  - `metadata.*` - 所有元数据管理权限
  - `settings.view` - 查看系统设置
- **特性**：
  - 系统角色（不可删除）
  - 可以管理内容和文件
  - 不能修改系统设置（只能查看）
  - 不能管理用户和角色

### 3. 编辑 (editor)
- **显示名称**：编辑
- **优先级**：500
- **描述**：内容编辑，可以管理图书、作者、标签等内容
- **具体权限**：
  - **图书**：`books.view`, `books.create`, `books.edit`, `books.upload`, `books.download`
  - **作者**：`authors.view`, `authors.create`, `authors.edit`
  - **标签**：`tags.view`, `tags.create`, `tags.edit`
  - **丛书**：`series.view`, `series.create`, `series.edit`
  - **书架**：`shelves.view`, `shelves.create`, `shelves.edit`, `shelves.delete`, `shelves.create_public`
  - **文件**：`files.view`, `files.upload`, `files.delete`
  - **元数据**：`metadata.search`, `metadata.fetch`
- **特性**：
  - 系统角色（不可删除）
  - 专注于内容管理
  - 可以上传和删除文件
  - 可以创建公开书架
  - 不能删除图书、作者、标签等（只能创建和编辑）

### 4. 普通用户 (user)
- **显示名称**：用户
- **优先级**：100
- **描述**：普通用户，可以浏览、下载图书，管理自己的书架
- **具体权限**：
  - **图书**：`books.download`
  - **书架**：`shelves.create`, `shelves.edit`, `shelves.delete`
- **特性**：
  - 系统角色（不可删除）
  - 默认注册角色
  - 可以下载图书
  - 可以创建和管理自己的私有书架
  - 依赖系统设置自动拥有浏览权限（无需明确授予）

### 5. 访客 (guest)
- **显示名称**：访客
- **优先级**：10
- **描述**：访客用户，只能浏览基本信息
- **权限**：无（空数组）
- **特性**：
  - 系统角色（不可删除）
  - 最低优先级
  - 只能浏览公开内容
  - 依赖系统设置控制可访问内容
  - 不能下载、上传或修改任何内容

---

### 角色权限说明

#### 通配符权限
- `*` - 全局通配符，匹配所有权限（仅超级管理员）
- `books.*` - 组通配符，匹配该组的所有权限（如 `books.view`, `books.create` 等）

#### 优先级规则
- 数字越大优先级越高
- 用户不能分配或修改比自己优先级高的角色
- 优先级用于防止越权操作

#### 系统角色保护
- 所有预定义角色都标记为 `is_system: true`
- 系统角色不能被删除
- 系统角色的基本信息可以修改，但建议保持不变

---

## 🔧 后端实现

### 1. 核心模型

#### 模型位置
- `app/Models/User.php` - 用户模型，包含角色关系和权限检查方法
- `app/Models/Role.php` - 角色模型，包含权限关系和通配符权限匹配
- `app/Models/Permission.php` - 权限模型，包含权限分组功能

#### 关键方法
**User 模型**：
- `roles()` - 用户的角色关系（多对多）
- `hasRole($role)` - 检查用户是否拥有指定角色
- `hasPermission($permission)` - 检查用户是否拥有指定权限
- `hasAnyPermission($permissions)` - 检查用户是否拥有任一权限
- `hasAllPermissions($permissions)` - 检查用户是否拥有所有权限
- `getHighestPriority()` - 获取用户最高角色优先级

**Role 模型**：
- `permissions()` - 角色的权限关系（多对多）
- `hasPermission($permission)` - 支持通配符匹配（如 `books.*` 匹配 `books.create`）
- `givePermissionTo($permission)` - 给角色分配权限
- `revokePermissionTo($permission)` - 撤销角色权限

**Permission 模型**：
- `getGrouped()` - 按组获取权限列表

### 2. 中间件

#### 权限检查中间件
- **位置**：`app/Http/Middleware/CheckPermission.php`
- **用途**：检查用户是否拥有指定权限
- **用法**：`->middleware('permission:books.edit')`

#### 角色检查中间件
- **位置**：`app/Http/Middleware/CheckRole.php`
- **用途**：检查用户是否拥有指定角色
- **用法**：`->middleware('role:admin,editor')`

### 3. API 路由

权限控制已集成到路由中，主要路由文件：
- **位置**：`routes/api.php`
- **示例**：
  - 角色管理：`/api/admin/roles` - 需要 `roles.view` 权限
  - 用户管理：`/api/admin/users` - 需要 `users.view` 权限
  - 权限列表：`/api/admin/permissions` - 需要 `roles.view` 权限
  - 图书管理：`/api/books` - 根据操作需要不同权限

### 4. 控制器

#### RolesController
- **位置**：`app/Http/Controllers/RolesController.php`
- **功能**：
  - `index()` - 获取角色列表（支持搜索）
  - `show($id)` - 获取单个角色详情
  - `store()` - 创建新角色
  - `update($id)` - 更新角色信息
  - `destroy($id)` - 删除角色（系统角色不可删除）
  - `syncPermissions($id)` - 同步角色权限

#### UsersController
- **位置**：`app/Http/Controllers/UsersController.php`
- **功能**：
  - `index()` - 获取用户列表（支持搜索和角色筛选）
  - `show($id)` - 获取用户详情
  - `store()` - 创建新用户
  - `update($id)` - 更新用户信息
  - `destroy($id)` - 删除用户
  - `syncRoles($id)` - 同步用户角色（带优先级检查）

#### PermissionsController
- **位置**：`app/Http/Controllers/PermissionsController.php`
- **功能**：
  - `index()` - 获取所有权限列表（按组分类）

### 5. 数据库 Seeders

#### PermissionsSeeder
- **位置**：`database/seeders/PermissionsSeeder.php`
- **功能**：初始化所有系统权限（10 个权限组，共 60+ 个权限）

#### RolesSeeder
- **位置**：`database/seeders/RolesSeeder.php`
- **功能**：创建预定义角色（super_admin、admin、editor、user、guest）并分配权限

---

## 🎨 前端实现

### 1. API 接口层

#### 类型定义
- **位置**：`src/api/types.ts`
- **内容**：定义 `Permission`、`Role`、`User` 等核心类型

#### 角色与权限 API
- **位置**：`src/api/roles.ts`
- **导出**：`rolesApi` 和 `permissionsApi`
- **方法**：
  - `rolesApi.list()` - 获取角色列表
  - `rolesApi.get(id)` - 获取角色详情
  - `rolesApi.create(data)` - 创建角色
  - `rolesApi.update(id, data)` - 更新角色
  - `rolesApi.delete(id)` - 删除角色
  - `rolesApi.syncPermissions(id, permissionIds)` - 同步角色权限
  - `permissionsApi.list()` - 获取所有权限列表

#### 用户管理 API
- **位置**：`src/api/users.ts`
- **导出**：`usersApi`
- **方法**：
  - `usersApi.list(params)` - 获取用户列表（支持搜索和角色筛选）
  - `usersApi.get(id)` - 获取用户详情
  - `usersApi.update(id, data)` - 更新用户信息
  - `usersApi.delete(id)` - 删除用户
  - `usersApi.syncRoles(id, roleIds)` - 同步用户角色

### 2. 权限检查 Composable

#### usePermission
- **位置**：`src/composables/usePermission.ts`
- **用途**：提供权限检查逻辑
- **方法**：
  - `hasPermission(permission)` - 检查单个权限
  - `hasAnyPermission(permissions)` - 检查是否有任一权限
  - `hasAllPermissions(permissions)` - 检查是否有所有权限
  - `hasRole(role)` - 检查角色
  - `can()`、`canAny()`、`canAll()`、`is()` - 别名方法
- **特性**：支持通配符权限匹配（如 `books.*`）

### 3. 权限指令

#### 自定义指令
- **位置**：`src/directives/permission.ts`
- **注册**：在 `src/main.ts` 中全局注册
- **指令列表**：
  - `v-permission="'books.edit'"` - 单个权限检查
  - `v-role="'admin'"` - 角色检查
  - `v-any-permission="['books.edit', 'books.create']"` - 多权限检查（任一）

#### 使用示例
```vue
<el-button v-permission="'roles.create'" type="primary">
  创建角色
</el-button>

<el-button v-role="'admin'" type="danger">
  管理员功能
</el-button>
```

### 4. 路由守卫

#### 权限路由
- **位置**：`src/router.ts`
- **配置**：在路由 `meta` 中定义权限要求
- **示例**：
```typescript
{
  path: '/admin/users',
  meta: {
    requiresAuth: true,
    permission: 'users.view'
  }
}
```
- **特性**：
  - 支持单个权限：`permission: 'books.edit'`
  - 支持多个权限（任一）：`permission: 'books.edit|books.create'`
  - 支持要求所有权限：`requireAllPermissions: true`

### 5. 页面组件

#### 角色管理页面
- **位置**：`src/pages/Admin/RoleList.vue`
- **功能**：
  - 显示角色列表
  - 创建/编辑/删除角色
  - 查看和分配权限
  - 系统角色保护（禁用编辑/删除按钮）

#### 用户管理页面
- **位置**：`src/pages/Admin/UserList.vue`
- **功能**：
  - 显示用户列表（支持搜索和角色筛选）
  - 分页显示
  - 为用户分配角色
  - 删除用户
  - 显示用户角色标签

#### 角色编辑对话框
- **位置**：`src/components/Admin/RoleEditor.vue`（或内嵌在 RoleList 中）
- **功能**：
  - 编辑角色基本信息
  - 选择和分配权限（按组分类显示）
  - 权限树形选择

### 6. 状态管理

#### authStore
- **位置**：`src/stores/auth.ts`
- **功能**：
  - 存储当前用户信息（包含角色和权限）
  - 在用户登录时加载角色和权限
  - 提供全局访问用户权限的接口
