<?php

class CategoryController extends Controller
{
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = $this->model(CategoryModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();

        $this->view('categories', [
            'categories' => $this->categoryModel->allByUser($user['id']),
        ]);
    }

    public function store(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/categories');
        }

        $user = auth_user();
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-wallet');

        if ($name === '') {
            flash('error', t('Category name is required.'));
            redirect('/categories');
        }

        $this->categoryModel->create($user['id'], $name, $icon ?: 'fa-wallet');
        flash('success', t('Category added.'));
        redirect('/categories');
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/categories');
        }

        $user = auth_user();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-wallet');

        if ($id <= 0 || $name === '') {
            flash('error', t('Invalid category data.'));
            redirect('/categories');
        }

        $this->categoryModel->update($id, $user['id'], $name, $icon);
        flash('success', t('Category updated.'));
        redirect('/categories');
    }

    public function delete(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/categories');
        }

        $user = auth_user();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->categoryModel->delete($id, $user['id']);
            flash('success', t('Category deleted.'));
        }

        redirect('/categories');
    }
}
