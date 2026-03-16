<?php

class ApiCategoryController extends ApiController
{
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = $this->model(CategoryModel::class);
    }

    public function index(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();

        $rows = $this->categoryModel->allByUser($user['id']);
        $this->success(['categories' => $rows]);
    }

    public function create(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $name = trim((string) ($input['name'] ?? ''));
        $icon = trim((string) ($input['icon'] ?? 'fa-wallet'));

        if ($name === '') {
            $this->error('Validation failed', 422, ['name is required']);
        }

        $this->categoryModel->create($user['id'], $name, $icon ?: 'fa-wallet');
        $this->success([], 'Category created', 201);
    }

    public function update(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $icon = trim((string) ($input['icon'] ?? 'fa-wallet'));

        if ($id <= 0 || $name === '') {
            $this->error('Validation failed', 422, ['id and name are required']);
        }

        $this->categoryModel->update($id, $user['id'], $name, $icon ?: 'fa-wallet');
        $this->success([], 'Category updated');
    }

    public function delete(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Validation failed', 422, ['id is required']);
        }

        $this->categoryModel->delete($id, $user['id']);
        $this->success([], 'Category deleted');
    }
}
