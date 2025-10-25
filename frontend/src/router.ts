import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import { isLoggedIn, isRole } from "@/stores/auth";

const routes: RouteRecordRaw[] = [
  { path: "/", name: "home", component: () => import("./pages/Home.vue") },
  {
    path: "/login",
    name: "login",
    component: () => import("./pages/Login.vue"),
  },
  {
    path: "/register",
    name: "register",
    component: () => import("./pages/Register.vue"),
  },
  {
    path: "/forgot",
    name: "forgot",
    component: () => import("./pages/ForgotPassword.vue"),
  },
  {
    path: "/reset",
    name: "reset",
    component: () => import("./pages/ResetPassword.vue"),
  },
  {
    path: "/books",
    name: "books",
    component: () => import("./pages/BookList.vue"),
  },
  {
    path: "/books/:id",
    name: "book-detail",
    component: () => import("./pages/BookDetail.vue"),
  },
  {
    path: "/reader",
    children: [
      {
        path: "/txt/:id",
        name: "reader-txt",
        component: () => import("./pages/Reader/TxtReader.vue"),
      },
      {
        path: "/pdf/:id",
        name: "reader-pdf",
        component: () => import("./pages/Reader/PdfReader.vue"),
      },
      {
        path: "/epub/:id",
        name: "reader-epub",
        component: () => import("./pages/Reader/EpubReader.vue"),
      },
    ],
  },
  {
    path: "/profile",
    name: "profile",
    component: () => import("./pages/Profile.vue"),
  },
  {
    path: "/settings",
    name: "user-settings",
    component: () => import("./pages/UserSettings.vue"),
  },
  {
    path: "/admin",
    redirect: {
      name: "admin-index",
    },
    children: [
      {
        path: "index",
        name: "admin-index",
        component: () => import("./pages/Admin/AdminIndex.vue"),
      },
      {
        path: "upload",
        name: "admin-upload",
        component: () => import("./pages/Admin/AdminBookUpload.vue"),
      },
      {
        path: "books",
        name: "admin-book-list",
        component: () => import("./pages/Admin/AdminBookList.vue"),
      },
      {
        path: "books/:id",
        name: "admin-book-edit",
        component: () => import("./pages/Admin/AdminBookEdit.vue"),
      },
      {
        path: "authors",
        name: "admin-author-list",
        component: () => import("./pages/Admin/AdminAuthorList.vue"),
      },
      {
        path: "tags",
        name: "admin-tag-list",
        component: () => import("./pages/Admin/AdminTagList.vue"),
      },
      {
        path: "shelves",
        name: "admin-shelf-list",
        component: () => import("./pages/Admin/AdminShelfList.vue"),
      },
      {
        path: "series",
        name: "admin-series-list",
        component: () => import("./pages/Admin/AdminSeriesList.vue"),
      },
      {
        path: "files",
        name: "admin-file-manager",
        component: () => import("./pages/Admin/AdminFileManager.vue"),
      },
      {
        path: "settings",
        name: "system-settings",
        component: () => import("./pages/Admin/AdminSystemSettings.vue"),
      },
      {
        path: "txt/chapters/:id?",
        name: "admin-txt-chapters",
        component: () => import("./pages/Admin/AdminTxtChapters.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// 路由守卫
router.beforeEach(async (to) => {
  const token = isLoggedIn();
  const isAdminRoute = to.path.startsWith("/admin");
  // 未登录禁止访问 /admin
  if (isAdminRoute && !token) {
    return { name: "login", query: { redirect: to.fullPath } };
  }
  // 管理路由：要求 admin 角色
  if (isAdminRoute && token) {
    if (!isRole("admin")) {
      return { name: "home" };
    }
  }
  // 若已登录仍访问登录页，重定向到书库
  if (to.name === "login" && token) {
    return { name: "books" };
  }
});

export default router;
